<?php

namespace OCA\Storj;

use OCA\Files_External\Service\BackendService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Container\ContainerInterface;

class LoadAdditionalBackendsEventListener implements IEventListener
{
	private BackendService $backendService;

	private ContainerInterface $container;

	public function __construct(BackendService $backendService, ContainerInterface $container)
	{
		$this->backendService = $backendService;
		$this->container      = $container;
	}

	public function handle(Event $event): void
	{
		$this->backendService->registerBackendProvider(
			$this->container->get(BackendProvider::class)
		);
	}
}
