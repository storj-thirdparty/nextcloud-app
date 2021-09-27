# Storj NextCloud App

**EXPERIMENTAL**

A Storj Community contributed decentralized storage backend for Nextcloud built on Storj DCS.

## Prerequisites

The PHP installation should have the FFI extension loaded and enabled unconditionally in php.ini:

```
extension=ffi

ffi.enable=true
```

## Installation

Install from the app store or place this app in the folder `apps` of the nextcloud installation

## Configuration

Storj works like any external object storage. See the documentation on docs.nextcloud.com:

* [Configuring Object Storage as Primary Storage](https://docs.nextcloud.com/server/20/admin_manual/configuration_files/primary_storage.html)
* [Configuring External Storage (GUI)](https://docs.nextcloud.com/server/20/admin_manual/configuration_files/external_storage_configuration_gui.html)

This is the configuration to set Storj as your primary storage:

```php
$CONFIG = [
    'objectstore' => [
        'class' => \OCA\Storj\StorjObjectStore::class,
        'arguments' => [
            'serialized_access' => 'myaccessgrant',
            'bucket' => 'mynextcloudbucket',
        ]
    ]
];
```

Primary storage is more responsive.

## Building

The app can be built by using the provided Makefile by running:

    make

## Publish to App Store

First get an account for the [App Store](http://apps.nextcloud.com/) then run:

    make && make appstore

The archive is located in build/artifacts/appstore and can then be uploaded to the App Store.

## Running tests
You can use the provided Makefile to run all tests by using:

    make test

This will run the PHP unit and integration tests and if a package.json is present in the **js/** folder will execute **npm run test**

Of course you can also install [PHPUnit](http://phpunit.de/getting-started.html) and use the configurations directly:

    phpunit -c phpunit.xml

or:

    phpunit -c phpunit.integration.xml

for integration tests

## Known issues and improvements

- Using external storage is slower than necessary because NextCloud request objects metadata separately during the same HTTP request. This can be improved by caching the results at the initial list operation. 
- Under unknown circumstances a segfault seems to occur.
