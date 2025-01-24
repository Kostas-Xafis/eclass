#! /bin/bash
sudo docker compose -f docker-compose.build.yaml build
if [ $? -ne 0 ]; then
  exit 1
fi

sudo docker compose down
sudo docker compose up -d