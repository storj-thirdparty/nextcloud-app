<?php

if (getenv('OBJECTSTORE_STORJ_BUCKET')) {
	$CONFIG = [
//		'app_install_overwrite' => [
//			'storj',
//		],
	    'objectstore' => [
			'class' => \OCA\Storj\StorjObjectStore::class,
			'arguments' => [
				// this is the same naming
				// convention as the other object storage backends.
				'serialized_access' => getenv('OBJECTSTORE_STORJ_ACCESS_GRANT'),
				'bucket' => getenv('OBJECTSTORE_STORJ_BUCKET'),
			]
		]
	];
}
