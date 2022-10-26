<?php

namespace OCA\Storj;

use Storj\Uplink\Config;
use Storj\Uplink\Project;
use Storj\Uplink\Uplink;

class ProjectFactory
{
	public static function fromParams(array $params): Project
	{
		return $params['project'] ?? (fn() =>
			self::fromSerializedAccess($params['serialized_access'])
		)();
	}

	public static function fromSerializedAccess(string $serializedAccess): Project
	{
		return Uplink::create()
			->parseAccess($serializedAccess)
			->openProject(
				(new Config())->prependUserAgent("nextcloud")
			);
	}
}
