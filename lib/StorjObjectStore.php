<?php

namespace OCA\Storj;

use OCA\Storj\AppInfo\Application;
use OCP\Files\ObjectStore\IObjectStore;
use Psr\Log\LoggerInterface;
use Storj\Uplink\Exception\Object\ObjectNotFound;
use Storj\Uplink\Project;
use Storj\Uplink\StreamResource\ReadProtocol;
use Storj\Uplink\Uplink;

/**
 * Used when Storj is set as main storage in config/config.php
 */
class StorjObjectStore implements IObjectStore
{
	protected Project $project;

	protected string $bucket;

	protected LoggerInterface $logger;

	/**
	 * @param array $params
	 */
	public function __construct($params)
	{
		// using nextcloud as main storage fails if this is not present
		Application::initAutoloader();

		$this->logger = \OC::$server->get(LoggerInterface::class);
		$this->project = ProjectFactory::fromParams($params);
		$this->bucket = $params['bucket'];
	}

	public function getStorageId(): string
	{
		return 'storj::' . $this->bucket;
	}

	public function readObject($urn)
	{
		$this->logger->debug('Storj::readObject("{urn}")', ['urn' => $urn]);

		$download = $this->project->downloadObject($this->bucket, $urn)->cursored();

		return ReadProtocol::createReadResource($download);
	}

	public function writeObject($urn, $stream, string $mimetype = null): void
	{
		$this->logger->debug('Storj::writeObject("{urn}")', ['urn' => $urn]);

		$this->project->ensureBucket($this->bucket);
		$upload = $this->project->uploadObject($this->bucket, $urn);
		$upload->writeFromResource($stream);
		$upload->commit();
	}

	public function deleteObject($urn): void
	{
		$this->logger->debug('Storj::deleteObject("{urn}")', ['urn' => $urn]);

		$this->project->deleteObject($this->bucket, $urn);
	}

	public function objectExists($urn): bool
	{
		$this->logger->debug('Storj::objectExists("{urn}")', ['urn' => $urn]);

		try {
			$this->project->statObject($this->bucket, $urn);
			return true;
		} catch (ObjectNotFound $e) {
			return false;
		}
	}

	public function copyObject($from, $to): void
	{
		$this->logger->debug(
			'Storj::copyObject("{from}", "{to}")',
			[
				'from' => $from,
				'to' => $to,
			]
		);

		$download = $this->project->downloadObject($this->bucket, $from);
		$upload = $this->project->uploadObject($this->bucket, $to);

		foreach ($download->iterate() as $chunk) {
			$upload->write($chunk);
		}

		$upload->commit();
	}
}
