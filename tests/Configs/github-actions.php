<?php

use Phpfastcache\Drivers\Ravendb\Config as RavendbConfig;

return (new RavendbConfig())
    ->setSerializeData(false)
    ->setItemDetailedDate(true)
    ->setHost([getenv('RAVENDB_TEST_DATABASE_HOSTNAME') ?: 'http://127.0.0.1:8082'])
    ->setCollectionName('phpfastcache')
    ->setDatabaseName('phpfastcache');
