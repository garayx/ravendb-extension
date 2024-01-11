<?php

/**
 *
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 *
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Ravendb driver');
$configFileName = __DIR__ . '/Configs/' . ($argv[1] ?? 'github-actions') . '.php';
if (!file_exists($configFileName)) {
    $configFileName = __DIR__ . '/Configs/github-actions.php';
}

try {
    $cacheInstance = CacheManager::getInstance('Ravendb', include $configFileName);
    $testHelper->runCRUDTests($cacheInstance);
    $testHelper->runGetAllItemsTests($cacheInstance);
} catch (PhpfastcacheDriverConnectException $e) {
    $testHelper->assertSkip('Ravendb server unavailable: ' . $e->getMessage());
    $testHelper->terminateTest();
}
$testHelper->terminateTest();
