<?php

namespace OCA\Storj;

use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\Config\IBackendProvider;

class BackendProvider implements IBackendProvider
{
	private StorjBackend $backend;

	public function __construct(StorjBackend $backend)
	{
		$this->backend = $backend;
	}

	/**
	 * @return Backend[]
	 */
	public function getBackends()
	{
		return [
			$this->backend,
		];
	}
}
