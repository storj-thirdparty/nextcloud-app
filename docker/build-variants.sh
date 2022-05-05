#!/bin/bash

NEXTCLOUD_VERSION=23.0.4

for VARIANT in apache fpm
do
    docker build . --tag storjthirdparty/nextcloud-app:${NEXTCLOUD_VERSION}-${VARIANT} \
    	--build-arg NEXTCLOUD_VERSION=${NEXTCLOUD_VERSION} \
        --build-arg VARIANT=${VARIANT}
done
