#!/bin/bash

# Make it not verbose
set +x

# This script is used to copy files from the host to the docker container
container_id=$(sudo docker ps -q -f "name=openeclass-release_393-eclass-1")

sudo docker cp ./template $container_id:/var/www/html/ > /dev/null 2>&1
sudo docker cp ./modules $container_id:/var/www/html/ > /dev/null 2>&1
sudo docker cp ./main $container_id:/var/www/html/ > /dev/null 2>&1
sudo docker cp ./include $container_id:/var/www/html/ > /dev/null 2>&1
sudo docker cp ./lang $container_id:/var/www/html/ > /dev/null 2>&1
sudo docker cp ./info $container_id:/var/www/html/ > /dev/null 2>&1