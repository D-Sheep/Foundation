<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 23/06/14
 */

namespace Foundation\Cache;

use Foundation\Logger;
use Incubator\Cache\Backend\Redis as RedisCacheBackend,
    Redis,
    Phalcon\Cache\Frontend\Data,
    Phalcon\Cache\Backend\File as FileCache;

class CacheFactory {

    const FILECACHE_IDENTIFICATOR = 'filecache';

    private $cacheConfig;

    /* @var array of \Phalcon\Cache\Backend */
    private $backends;

    /* @var int
     * 0 = filecache; 1 = redis;
     */
    private $type;

    /** @var Data */
    private $defaultFrontend;

    /** @var Redis */
    private $redis;

    /*
     * Sets cache config
     * @param  $cacheConfig Part of config
     */
    public function __construct ( $cacheConfig ){
        $this->cacheConfig = $cacheConfig;
        $this->connected = false;
        $this->type = null;
        $this->backends = [];
        $this->defaultFrontend = null;
        $this->redis = null;
    }

    /*
     * Returns data from cache, if exists
     * @param String $prefix Identificator of cached data
     * @param Frontend $frontend Frontend
     * @param String $cacheDir Dir, where should be the data stored
     * @return Phalcon\Cache\Backend
     */
    public function getCacheBackend ( $prefix, $frontend = null, $cacheDir = null ){
        if ( isset($this->backends[$prefix]) ) {
            return $this->backends[$prefix];
        }

        if ($this->type === null){
            $this->detectType();
        }

        //if cache dir is not defined
        if ($cacheDir === null) {
            $cacheDir = $this->cacheConfig->filePath;
        }

        $usedFrontend = $frontend;
        if ( $usedFrontend === null ) {
            if ( $this->defaultFrontend == null ){
                $this->defaultFrontend = new Data(array("lifetime" => CACHE_MAX_LIFETIME));
            }
            $usedFrontend = $this->defaultFrontend;
        }

        if ($this->type == 0){
            $cache = new FileCache(
                $usedFrontend,
                array('cacheDir' => $cacheDir,
                        'prefix' => $prefix));
        } else {
            //Connect to redis
            if ($this->redis === null){
                $this->redis = new Redis();
                $this->redis->connect($this->cacheConfig->ip, $this->cacheConfig->port);
            }

            //Create the cache passing the connection
            $cache = new RedisCacheBackend(
                $usedFrontend,
                array('redis' => $this->redis,
                        'prefix' => $prefix
            ));
        }

        if ($frontend === null) {
            $this->backends[$prefix] = $cache;
        }
        return $cache;
    }

    private function detectType(){
        if(strcmp($this->cacheConfig->indentificator, self::FILECACHE_IDENTIFICATOR) == 0){
            $this->type = 0;
        } else {
            $this->type = 1;
        }
    }

}
