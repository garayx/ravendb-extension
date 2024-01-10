<?php

use Phpfastcache\Drivers\Ravendb\Config as RavendbConfig;

return (new RavendbConfig())
    ->setSerializeData(false)
    ->setItemDetailedDate(true)
    ->setHost(['http://127.0.0.1:8082'])
    ->setCollectionName('phpfastcache2')
    ->setDatabaseName('phpfastcache');
