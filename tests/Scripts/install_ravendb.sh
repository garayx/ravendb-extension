#!/bin/bash

curl 'http://live-test.ravendb.net/admin/databases?name=phpfastcache&replicationFactor=1' -X PUT \
  -H 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:122.0) Gecko/20100101 Firefox/122.0' \
  -H 'Accept: */*' \
  -H 'Accept-Language: en-US,en;q=0.5' \
  -H 'Accept-Encoding: gzip, deflate' \
  -H 'Content-Type: application/json; charset=utf-8' \
  -H 'Raven-Studio-Version: 5.4.0.0' \
  -H 'X-Requested-With: XMLHttpRequest' \
  -H 'Origin: http://live-test.ravendb.net' \
  -H 'Connection: keep-alive' \
  -H 'Referer: http://live-test.ravendb.net/studio/index.html' \
  -H 'Pragma: no-cache' \
  -H 'Cache-Control: no-cache' \
  --data-raw '{"DatabaseName":"phpfastcache","Settings":{},"Disabled":false,"Encrypted":false,"Topology":{"DynamicNodesDistribution":false},"Sharding":null}';
