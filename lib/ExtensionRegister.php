<?php

declare(strict_types=1);

namespace Phpfastcache;

use Phpfastcache\Extensions\Drivers\Ravendb\{Config, Driver, Event, Item};

// Semver Compatibility until v10
class_alias(Config::class, Drivers\Ravendb\Config::class);
class_alias(Driver::class, Drivers\Ravendb\Driver::class);
class_alias(Event::class, Drivers\Ravendb\Event::class);
class_alias(Item::class, Drivers\Ravendb\Item::class);

ExtensionManager::registerExtension('Ravendb', Driver::class);
