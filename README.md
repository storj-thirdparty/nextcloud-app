# Storj NextCloud App

**EXPERIMENTAL**

A Storj Community contributed decentralized storage backend for Nextcloud built on Storj DCS.

## Prerequisites

The PHP installation should have the FFI extension loaded and enabled unconditionally in php.ini:

```
extension=ffi

ffi.enable=true
```

### Specific for docker version of Nextcloud
By default the Nextcloud docker image comes without `FFI` support. But you can install it inside the container:
```
docker exec -it nextcloud bash
root@5f11b342df44:/var/www/html# apt update && apt install libffi-dev
root@5f11b342df44:/var/www/html# docker-php-ext-install ffi
```
Now you need to enable ffi in `php.ini`
Copy `php.ini` template:
```
root@5f11b342df44:/var/www/html# cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
```
And enable `ffi` extension:
```
root@5f11b342df44:/var/www/html# sed -i 's/^;extension=ffi/extension=ffi/g; s/^;ffi.enable=preload/ffi.enable=true/g' /usr/local/etc/php/php.ini
```
Now you need to reload Apache
```
root@5f11b342df44:/var/www/html# apachectl graceful
```

To make changes permanent you need to build your own image. Create a `Dockerfile`:
```Dockerfile
FROM nextcloud
RUN apt update && apt install libffi-dev -y && docker-php-ext-install ffi
RUN sed 's/^;extension=ffi/extension=ffi/g; s/^;ffi.enable=preload/ffi.enable=true/g' /usr/local/etc/php/php.ini-production > /usr/local/etc/php/php.ini
```
And build it:
```
docker build . -t my/nextcloud
```
Now you can run your own image, replace `docker run ... nextcloud` to `docker run ... my/nextcloud`

## Installation

Install from the app store or place this app in the folder `apps` of the nextcloud installation

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

## Known issues and improvements

- Using external storage is slower than necessary because NextCloud request objects metadata separately during the same HTTP request. This can be improved by caching the results at the initial list operation. 
- Under unknown circumstances a segfault seems to occur.
