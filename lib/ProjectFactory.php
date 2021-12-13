<?php

namespace OCA\Storj;

use Storj\Uplink\Project;
use Storj\Uplink\Uplink;

class ProjectFactory
{
	public static function fromParams(array $params): Project
	{
		return $params['project'] ?? (fn() =>
			Uplink::create()
				->parseAccess($params['serialized_access'])
				->openProject()
		)();
	}
}
