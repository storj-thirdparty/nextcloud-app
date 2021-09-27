<?php

namespace OCA\Storj\AppInfo;

use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\Config\IBackendProvider;
use OCA\Storj\LoadAdditionalBackendsEventListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap
{
	public function __construct()
	{
		parent::__construct('Storj');
	}

	public static function initAutoloader(): void
	{
		require_once __DIR__ . '/../../vendor/autoload.php';
	}

	public function register(IRegistrationContext $context): void
	{
		self::initAutoloader();

		$context->registerEventListener(
			'OCA\\Files_External::loadAdditionalBackends',
			LoadAdditionalBackendsEventListener::class
		);
	}

	public function boot(IBootContext $context): void
	{
	}
}
