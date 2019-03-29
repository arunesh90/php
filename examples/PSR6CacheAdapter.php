<?php
declare(strict_types=1);

/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date      2019
 * @license   http://opensource.org/licenses/MIT
 */

use Cache\Adapter\Apc\ApcCachePool;
use Secretary\Adapter\Cache\PSR6CacheAdapter;
use Secretary\Adapter\Hashicorp\Vault\HashicorpVaultAdapter;

require_once __DIR__.'/vendor/autoload.php';

$manager = new \Secretary\Manager(new PSR6CacheAdapter(new HashicorpVaultAdapter(), new ApcCachePool()));

$manager->putSecret('baz', ['foo' => 'foobar']);

var_dump(
    $manager->getSecret('baz', ['ttl' => 1000 * 60]),
    $manager->getSecret('baz')['foo'] // Pulls from cache!
);

$manager->deleteSecret('baz');


var_dump($manager->getSecret('baz')); // 404, delete cleared the cache