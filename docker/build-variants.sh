#!/bin/bash

NEXTCLOUD_VERSION=25.0.0

for VARIANT in apache fpm
do
    docker build . --tag storjthirdparty/nextcloud-app:${NEXTCLOUD_VERSION}-${VARIANT} \
    	--tag storjthirdparty/nextcloud-app:latest-${VARIANT} \
    	--build-arg NEXTCLOUD_VERSION=${NEXTCLOUD_VERSION} \
        --build-arg VARIANT=${VARIANT}
done

docker tag storjthirdparty/nextcloud-app:latest-apache storjthirdparty/nextcloud-app:latest
