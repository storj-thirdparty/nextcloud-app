<?php

namespace OCA\Storj;

use ErrorException;
use Exception;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\StorageConfig;
use OCP\Files\StorageNotAvailableException;
use OCP\IL10N;
use OCP\IUser;
use Storj\Uplink\Uplink;
use Throwable;

class StorjBackend extends \OCA\Files_External\Lib\Backend\Backend
{
	public function __construct(IL10N $l)
	{
		$this
			->setIdentifier('storj_uplink')
			->setStorageClass(StorjStorage::class)
			->setText('Storj')
			->addParameter(new DefinitionParameter('bucket', $l->t('Bucket')))
			->addParameter(new DefinitionParameter('serialized_access', $l->t('Serialized access')));
	}

	/**
	 * When the user saves the config, create the bucket
	 *
	 * @throws StorageNotAvailableException
	 */
	public function manipulateStorageConfig(StorageConfig &$storage, IUser $user = null)
	{
		$bucket = $storage->getBackendOption('bucket');
		$accessGrant = $storage->getBackendOption('serialized_access');

		try {
			try {
				$uplink = Uplink::create();
				$access = $uplink->parseAccess($accessGrant);
				$project = $access->openProject();
				$project->ensureBucket($bucket);
			} catch (\Error $e) {
				// convert to exception or it will fail type check
				throw new ErrorException(
					$e->getMessage(),
					$e->getCode(),
					1,
					$e->getFile(),
					$e->getLine(),
					$e
				);
			}
		} catch (Exception $exception) {
			// Will display important information for the user to fix the problemn
			throw new StorageNotAvailableException(
				$exception->getMessage(),
				// code must be nonzero to display error icon
				$exception->getCode() ?: 1,
				$exception
			);
		}
	}
}
