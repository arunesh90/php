<?php
declare(strict_types=1);

/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date      2019
 * @license   http://opensource.org/licenses/MIT
 */


namespace Secretary\Adapter\Cache;


use Psr\Cache\CacheItemPoolInterface;
use Secretary\Adapter\AbstractAdapter;
use Secretary\Adapter\AdapterInterface;
use Secretary\Adapter\Secret;
use Secretary\Helper\ArrayHelper;

/**
 * Class PSR6CacheAdapter
 *
 * @package Secretary\Adapter\Cache
 */
final class PSR6CacheAdapter extends AbstractAdapter
{
    /**
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * CacheAdapter constructor.
     *
     * @param AdapterInterface       $adapter
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(AdapterInterface $adapter, CacheItemPoolInterface $cache)
    {
        $this->adapter = $adapter;
        $this->cache   = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecret(string $key, ?array $options = []): Secret
    {
        [$ttl] = ArrayHelper::remove($options, 'ttl');

        return $this->memoize(
            $key,
            function () use ($key, $options) {
                return $this->adapter->getSecret($key, $options);
            },
            $ttl
        );
    }

    /**
     * {@inheritdoc}
     */
    public function putSecret(string $key, $value, ?array $options = []): void
    {
        [$ttl] = ArrayHelper::remove($options, 'ttl');

        $this->adapter->putSecret($key, $value, $options);
        if ($this->cache->hasItem($key) || $ttl === 0) {
            $this->cache->deleteItem($key);

            return;
        }

        $item = $this->cache->getItem($key);
        $item->set($value);
        if (!empty($ttl)) {
            $item->expiresAfter($ttl);
        }
        $this->cache->save($item);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteSecret(string $key, ?array $options = []): void
    {
        $this->adapter->deleteSecret($key, $options);
        if ($this->cache->hasItem($key)) {
            $this->cache->deleteItem($key);
        }
    }

    /**
     * @param string   $key
     * @param callable $callback
     * @param int|null $ttl
     *
     * @return mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function memoize(string $key, callable $callback, int $ttl = null)
    {
        $item = $this->cache->getItem($key);
        if ($item !== null) {
            return $item->get();
        }

        $cachedValue = $callback();
        $item->set($cachedValue);
        if ($ttl !== null) {
            $item->expiresAfter($ttl);
        }
        $this->cache->save($item);

        return $cachedValue;
    }
}
