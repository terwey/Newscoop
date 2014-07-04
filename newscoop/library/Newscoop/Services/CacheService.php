<?php
/**
 * @package Newscoop
 * @copyright 2014 Sourcefabric o.p.s.
 * @author Paweł Mikołąjczuk <pawel.mikolajczuk@sourcefabric.org>
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\Services;

/**
 * Cache service
 */
class CacheService
{
    /**
     * Instance of cache driver
     *
     * @var \Doctrine\Common\Cache\CacheProvider
     */
    protected $cacheDriver;

    /**
     * Initialize cache driver (based on system preferences settings, default is array)
     *
     * @param \Newscoop\NewscoopBundle\Services\SystemPreferencesService $systemPreferences
     */
    public function __construct($systemPreferences)
    {
        try {
            switch ($systemPreferences->get('DBCacheEngine', 'array')) {
                case 'apc':
                    $this->cacheDriver = new \Doctrine\Common\Cache\ApcCache();
                    break;
                case 'memcache':
                    $memcache = new \Memcache();
                    $memcache->connect(
                        $systemPreferences->get('DBCacheEngineHost', '127.0.0.1'), 
                        $systemPreferences->get('DBCacheEnginePort', '11211')
                    );

                    $this->cacheDriver = new \Doctrine\Common\Cache\MemcacheCache();
                    $this->cacheDriver->setMemcache($memcache);
                    break;
                case 'memcached':
                    $memcached = new \Memcached();
                    $memcached->addServer(
                        $systemPreferences->get('DBCacheEngineHost', '127.0.0.1'), 
                        $systemPreferences->get('DBCacheEnginePort', '11211')
                    );

                    $this->cacheDriver = new \Doctrine\Common\Cache\MemcachedCache();
                    $this->cacheDriver->setMemcached($memcached);
                    break;
                case 'xcache':
                    if (ini_get('xcache.var_size')) {
                        $this->cacheDriver = new \Doctrine\Common\Cache\XcacheCache();
                    } else {
                        $this->cacheDriver = new \Doctrine\Common\Cache\ArrayCache();
                    }
                    break;
                case 'redis':
                    $redis = new \Redis();
                    $redis->connect(
                        $systemPreferences->get('DBCacheEngineHost', '127.0.0.1'), 
                        $systemPreferences->get('DBCacheEnginePort', '6379')
                    );
                    $this->cacheDriver = new \Doctrine\Common\Cache\RedisCache();
                    $this->cacheDriver->setRedis($redis);
                    break;
                default:
                    $this->cacheDriver = new \Doctrine\Common\Cache\ArrayCache();
                    break;
            }
        } catch (\Exception $e) {
            $this->cacheDriver = new \Doctrine\Common\Cache\ArrayCache();
        }
    }

    /**
     * Fetch data from cache
     *
     * @param string|array $id
     *
     * @return mixed
     */
    public function fetch($id)
    {
        return $this->cacheDriver->fetch($this->getCacheKey($id));
    }

    /**
     * Check if cache have provided key
     *
     * @param string|array $id
     *
     * @return boolean
     */
    public function contains($id)
    {
        return $this->cacheDriver->contains($this->getCacheKey($id));
    }

    /**
     * Save new value in cache
     *
     * @param string|array $id
     * @param mixed        $data
     * @param integer      $lifeTime
     *
     * @return boolean
     */
    public function save($id, $data, $lifeTime = 1400)
    {
        return $this->cacheDriver->save($this->getCacheKey($id), $data, $lifeTime);
    }

    /**
     * Delete key from cache
     *
     * @param string|array $id
     *
     * @return boolean
     */
    public function delete($id)
    {
        return $this->cacheDriver->delete($this->getCacheKey($id));
    }

    public function getCacheKey($id, $namespace = null)
    {
        if (is_array($id)) {
            $id = implode('__', $id);
        }

        if ($namespace) {
            $namespace = $this->getNamespace($namespace);

            return $namespace.'__'.$id;
        }

        return $id;
    }

    public function getNamespace($namespace)
    {
        if ($this->cacheDriver->contains($namespace)) {
            return $this->cacheDriver->fetch($namespace);
        }

        $value = $namespace .'|'.time();
        $this->cacheDriver->save($namespace, $value);

        return $value;
    }

    public function clearNamespace($namespace)
    {
        $this->cacheDriver->save($namespace, time());
    }

    /**
     * Get array of avaiable cache drivers (based on system configurations)
     *
     * @return array
     */
    public function getAvailableCacheEngines()
    {
        $engines = array();

        if (extension_loaded('apc') && ini_get('apc.enabled')) {
            $engines['Apc'] = 'apc';
        }

        if (class_exists('\Redis')) {
            $engines['Redis'] = 'redis';
        }

        if (class_exists('\Memcache')) {
            $engines['Memcache'] = 'memcache';
        }

        if (class_exists('\Memcached')) {
            $engines['Memcached'] = 'memcached';
        }

        if (extension_loaded('xcache') && ini_get('xcache.cacher')) {
            $engines['Xcache'] = 'xcache';
        }

        return $engines;
    }

    /**
     * Get cache driver instance
     *
     * @return \Doctrine\Common\Cache\CacheProvider
     */
    public function getCacheDriver()
    {
        return $this->cacheDriver;
    }
}
