# Storj NextCloud App

Adds external storage support for Storj Decentralized Cloud Storage.

Storj Community contributed.

## Prerequisites

Currently only works on x64.

The PHP installation should have the FFI extension loaded and enabled unconditionally in php.ini:

```
extension=ffi

ffi.enable=true
```

## Installation

Install from the [App store](https://apps.nextcloud.com/apps/storj) or place this app in the folder `apps` of the nextcloud installation

## Configuration

Storj works like any external object storage. See the documentation on docs.nextcloud.com:

* [Configuring External Storage (GUI)](https://docs.nextcloud.com/server/20/admin_manual/configuration_files/external_storage_configuration_gui.html)
* [Configuring Object Storage as Primary Storage](https://docs.nextcloud.com/server/20/admin_manual/configuration_files/primary_storage.html)

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

Primary storage is more responsive because it relies more on your local database, but does create small files, which is not the best usecase for Storj.

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

## Known issues

- Enabling Xdebug profiling or debugging will cause a segfault
