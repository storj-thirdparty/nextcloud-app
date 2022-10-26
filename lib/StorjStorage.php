<?php


namespace OCA\Storj;


use Generator;
use Icewind\Streams\IteratorDirectory;
use OC\Cache\CappedMemoryCache;
use OC\Files\Storage\Common;
use OCA\Storj\AppInfo\Application;
use OCP\Files\ObjectStore\IObjectStore;
use OCP\ICache;
use Psr\Log\LoggerInterface;
use Storj\Uplink\Exception\Object\ObjectNotFound;
use Storj\Uplink\Exception\UplinkException;
use Storj\Uplink\ListObjectsOptions;
use Storj\Uplink\ObjectInfo;
use Storj\Uplink\Project;
use Storj\Uplink\StreamResource\WriteProtocol;
use Storj\Uplink\Uplink;
use Throwable;
use function Sabre\Xml\Deserializer\functionCaller;

/**
 * Used when Storj is set as external storage through the GUI.
 */
class StorjStorage extends Common implements IObjectStore
{
	protected Project $project;

	protected string $bucket;

	protected LoggerInterface $logger;

	protected StorjObjectStore $storjObjectStore;

	/**
	 * NextCloud will ask for different properties of the same remote object multiple times,
	 * Therefor this object needs an internal cache for speed.
	 *
	 * @var ICache<ObjectInfo>
	 */
	protected ICache $objectInfoCache;

	public function __construct($params)
	{
		parent::__construct($params);

		$this->logger = \OC::$server->get(LoggerInterface::class);
		$this->objectInfoCache = new CappedMemoryCache();

		$project = ProjectFactory::fromParams($params);

		$this->project = $project;
		$this->bucket = $params['bucket'];

		$params['project'] = $project;
		$this->storjObjectStore = new StorjObjectStore($params);
	}

	public function getStorageId(): string
	{
		return $this->storjObjectStore->getStorageId();
	}

	public function readObject($urn)
	{
		return $this->storjObjectStore->readObject($urn);
	}

	public function writeObject($urn, $stream, string $mimetype = null): void
	{
		$this->storjObjectStore->writeObject($urn, $stream, $mimetype);
	}

	public function deleteObject($urn): void
	{
		$this->storjObjectStore->deleteObject($urn);
	}

	public function objectExists($urn): bool
	{
		return $this->storjObjectStore->objectExists($urn);
	}

	public function copyObject($from, $to): void
	{
		$this->storjObjectStore->copyObject($from, $to);
	}

	public function getId(): string
	{
		return $this->getStorageId();
	}

	public function mkdir($path): bool
	{
		$this->logger->debug('Storj::mkdir("{path}")', ['path' => $path]);

		$path = $this->normalizePath($path);

		try {
			// this is the same convention as the satellite object browser
			$upload = $this->project->uploadObject($this->bucket, $path . '/.file_placeholder');
			$upload->commit();
			$objectInfo = new ObjectInfo($path, true, null, null);
			$this->objectInfoCache->set($path, $objectInfo);
		} catch (UplinkException $e) {
			$this->logger->error($e);
			return false;
		}

		return true;
	}

	public function rmdir($path): bool
	{
		$this->logger->debug('Storj::rmdir("{path}")', ['path' => $path]);

		$path = $this->normalizePath($path);

		try {
			$listOptions = (new ListObjectsOptions)
				->withPrefix($path . '/')
				->withRecursive(true);

			foreach ($this->project->listObjects($this->bucket, $listOptions) as $object) {
				$this->project->deleteObject($this->bucket, $object->getKey());
				$this->objectInfoCache->remove($object->getKey());
			}

			$this->objectInfoCache->remove($path);
		} catch (UplinkException $e) {
			$this->logger->error('Error removing dir {path}: {error}', [
				'path' => $path,
				'error' => $e
			]);
			return false;
		}

		return true;
	}

	/**
	 * @param string $path
	 * @return resource|false
	 */
	public function opendir($path)
	{
		$this->logger->debug('Storj::opendir("{path}")', ['path' => $path]);

		$path = $this->normalizePath($path);
		if ($path !== "") {
			$path = "$path/";
		}

		$listObjectOptions = (new ListObjectsOptions())
			->withPrefix($path)
			->withCursor('')
			->withSystemMetadata(true)
			->withCustomMetadata(true)
			->withRecursive(false);

		$objectInfoIterator = $this->project->listObjects($this->bucket, $listObjectOptions);

		$iterator = function() use ($objectInfoIterator): Generator {
			foreach ($objectInfoIterator as $objectInfo) {
				$path = $this->normalizePath($objectInfo->getKey());
				$this->objectInfoCache->set($path, $objectInfo);
				$basename = basename($objectInfo->getKey());
				if ($basename !== '.file_placeholder') {
					yield $basename;
				}
			}
		};

		return IteratorDirectory::wrap($iterator());
	}

	public function stat($path)
	{
		$this->logger->debug('Storj::stat("{path}")', ['path' => $path]);

		$path = $this->normalizePath($path);

		if ($this->is_dir($path)) {
			return [
				'size' => -1,
				'mtime' => time(),
			];
		}

		$objectInfo = $this->objectInfoCache->get($path);

		if ($objectInfo === null) {
			try {
				$objectInfo = $this->project->statObject($this->bucket, $path);
				$this->objectInfoCache->set($path, $objectInfo);
			} catch (UplinkException $e) {
				$this->logger->error(
					'Storj::stat("{path}") {exception} thrown "{message}"',
					[
						'path' => $path,
						'exception' => get_class($e),
						'message' => $e->getMessage(),
					]
				);
				return false;
			}
		}

		$systemMetadata = $objectInfo->getSystemMetadata();

		return [
			'size' => $systemMetadata->getContentLength(),
			'mtime' => $systemMetadata->getCreated()->format('U'),
		];
	}

	/**
	 * Nextcloud did not tell the file type during the upload.
	 * So why does it expect us to know?
	 * Logic copied from the other implementations.
	 *
	 * TODO: check if there is any added value to determining a more exact file type
	 */
	public function filetype($path)
	{
		$this->logger->debug('Storj::filetype("{path}")', ['path' => $path]);

		$path = $this->normalizePath($path);

		// Nextcloud wants this
		if ($path === '') {
			return 'dir';
		}

		$objectInfo = $this->objectInfoCache->get($path);

		if ($objectInfo === null) {
			try {
				$objectInfo = $this->project->statObject($this->bucket, $path);
				$this->objectInfoCache->set($path, $objectInfo);
			} catch (ObjectNotFound $e) {
				// its not an object, check if it's a prefix
				$objects = $this->project->listObjects($this->bucket, (new ListObjectsOptions())
					->withPrefix($path . '/'));
				if ($objects->valid()) {
					$objectInfo = new ObjectInfo($path, true, null, null);
					$this->objectInfoCache->set($path, $objectInfo);
					return 'dir';
				} else {
					// its neither an object nor a prefix: it doesn't exist
					return false;
				}
			}
		}

		if ($objectInfo->isPrefix()) {
			$this->logger->debug("$path is dir");
			return 'dir';
		}

		// this is an old approach, remove at some point
		if (($objectInfo->getCustomMetadata()['ContentType'] ?? null)
			=== 'application/vnd.storj.directory') {
			$this->logger->debug("$path is legacy dir, removing");
			$this->project->deleteObject($this->bucket, $path);
			return 'dir';
		}

		$this->logger->debug("$path is file");
		return 'file';
	}

	public function file_exists($path)
	{
		$this->logger->debug('Storj::file_exists("{path}")', ['path' => $path]);

		$path = $this->normalizePath($path);

		// Nextcloud wants this
		if ($path === '') {
			return true;
		}

		if ($this->objectInfoCache->get($path) !== null) {
			return true;
		}

		return $this->filetype($path) !== false;
	}

	public function unlink($path)
	{
		$this->logger->debug('Storj::unlink("{path}")', ['path' => $path]);

		$path = $this->normalizePath($path);

		$this->project->deleteObject($this->bucket, $path);
		$this->objectInfoCache->remove($path);

		return true;
	}

	public function fopen($path, $mode)
	{
		$this->logger->debug(
			'Storj::fopen("{path}", "{mode}")',
			[
				'path' => $path,
				'mode' => $mode,
			]
		);

		$path = $this->normalizePath($path);

		switch ($mode) {
			case 'r':
			case 'rb':
				try {
					return $this->readObject($path);
				} catch (UplinkException $e) {
					return false;
				}
			case 'w':
			case 'wb':
				$upload = $this->project->uploadObject($this->bucket, $path);
				return WriteProtocol::createWriteResource($upload);
			case 'a':
			case 'ab':
			case 'r+':
			case 'w+':
			case 'wb+':
			case 'a+':
			case 'x':
			case 'x+':
			case 'c':
			case 'c+':
				$this->logger->error('Partial writes not implemented for Storj');
				// Storj does not have the ability to do partial writes.
				// We could do a trick here: copy the Storj file to a temp file, change it, and replace the remote file.
				// This would probably be better to implement in the Uplink-php library.
		}
		return false;
	}

	/**
	 * Nextcloud will call this after upload to alter the creation time,
	 * but this is not supported by Storj.
	 */
	public function touch($path, $mtime = null)
	{
		$this->logger->debug('Storj::touch("{path}")', ['path' => $path]);

		$path = $this->normalizePath($path);

		if ($this->file_exists($path)) {
			// matches native PHP touch behavior
			return true;
		}

		try {
			$upload = $this->project->uploadObject($this->bucket, $path);
			$upload->commit();
		} catch (UplinkException $e) {
			$this->logger->error($e->getMessage());
			return false;
		}

		return true;
	}

	private function normalizePath($path)
	{
		$path = trim($path, '/');

		if ($path === ".") {
			return "";
		}

		return $path;
	}
}
