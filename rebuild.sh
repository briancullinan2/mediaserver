#!/bin/bash
cd "$(dirname "$0")"

docker stop mediaserver
docker stop mediaserverdb
docker ps -q -a | xargs docker rm
docker build -t megamind/mediaserver ./
docker build -t megamind/mediaserverdb ./db/
docker run --name mediaserverdb -d megamind/mediaserverdb --sql_mode=""
docker run --name mediaserver -e SYMFONY__DATABASE__HOST=mediaserverdb -v /Users/briancullinan/Documents/mediaserver:/var/www -p 8086:80 -d mediaserver
