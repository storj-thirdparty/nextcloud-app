# Storj NextCloud App

Adds external storage support for Storj Decentralized Cloud Storage.

Storj Community contributed.

* [Prerequisites](#prerequisites)
	* [Detailed instructions for docker version of Nextcloud](#detailed-instructions-for-docker-version-of-nextcloud)
* [Installation](#installation)
* [Configuration](#configuration)
* [Building](#building)
* [Publish to App Store](#publish-to-app-store)
* [Running tests](#running-tests)
* [Known issues](#known-issues)

## Prerequisites

Linux. x86_64 or ARM64. libffi installed.

The PHP installation should have the FFI extension loaded and enabled unconditionally in php.ini:

```
extension=ffi

ffi.enable=true
```

Detailed instructions depend on your distro.

### Detailed instructions for docker version of Nextcloud

By default the Nextcloud docker image comes without `FFI` support. But you can install it inside the container:

```
docker exec -it nextcloud bash
root@5f11b342df44:/var/www/html# apt update 
root@5f11b342df44:/var/www/html# apt install libffi-dev
root@5f11b342df44:/var/www/html# docker-php-ext-install ffi
```

("nextcloud" is the name you gave the container earlier)

The extension is now enabled through `/usr/local/etc/php/conf.d/docker-php-ext-ffi.ini`

Also allow loading libraries at runtime:

```
root@5f11b342df44:/var/www/html# echo ffi.enable=true > /usr/local/etc/php/conf.d/ffi.ini
```

Reload Apache:

```
root@5f11b342df44:/var/www/html# apachectl graceful
```

To make changes permanent you need to build your own image. Create a `Dockerfile`:

```Dockerfile
FROM nextcloud
RUN apt update && apt install -y libffi-dev && docker-php-ext-install ffi
RUN echo ffi.enable=true > /usr/local/etc/php/conf.d/ffi.ini
```

And build it:

```
docker build . -t my/nextcloud
```

Now you can run your own image, change `docker run ... nextcloud` to `docker run ... my/nextcloud`

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

Bump the version in [./appinfo/info.xml](./appinfo/info.xml)

Build un:

    make && make appstore

The archive is located in build/artifacts/appstore. 
Follow the instructions at [https://apps.nextcloud.com/developer/apps/releases/new](http://apps.nextcloud.com/) to upload the app to the store.

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
