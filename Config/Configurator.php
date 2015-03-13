<?php



namespace Foundation\Config;

use Phalcon\Config;

class Configurator {

    const DEVELOPMENT = 'development',
          PRODUCTION = 'production';

    /* @var String */
    private $mode;

    /* @var boolean */
    private $debug;

    /* @var \Phalcon\Config */
    private $config;

    /* @var boolean */
    private $production;

    /* @var boolean */
    private $development;

    /* @var boolean */
    private $testCache;

    private $configTypes;

    private $addresses;

    private $environments;

    protected $configPath;

    protected $cachePath;

    /**
     * Update config - detect mode and include subconfigs
     * @param String $configPath Source path to config files
     * @param String $configPath Source path to cache files
     * @param boolean $cacheTest indicates wheather use cachce or not
     */
    public function __construct($configPath, $cachePath, $cacheTest = false){
        $this->testCache = $cacheTest;
        $this->configPath = $configPath;
        $this->cachePath = $cachePath;
    }

    public function initialize() {

        if (isset($_SERVER["HTTP_HOST"])) {
            $baseUrl = $_SERVER["HTTP_HOST"];
        } else {
            $baseUrl = "localhost";
        }

        if (!isset($this->environments[$baseUrl] )) {
            $this->environments[$baseUrl] = self::DEVELOPMENT;
            //throw new ConfigException("Cant detect Url");
        }

        $this->mode = $this->environments[$baseUrl];
        $this->debug = $this->detectDebugMode();

        echo "mode: ".$this->mode."<br>";
        if ( strcmp($this->mode, Configurator::DEVELOPMENT==0)){
            if (!$this->debug) $this->debug = true;
            $this->development = true;
            $this->production = false;
        } else if ( strcmp($this->mode, Configurator::PRODUCTION)==0 ){
            $this->development = false;
            $this->production = true;
        }

        echo "je produkce: ";
        var_dump($this->production);
        exit();

        if ($this->development && !$this->testCache){
            $this->getConfig($this->configPath, $this->configTypes);
        } else {

            $frontCache = new \Phalcon\Cache\Frontend\Data(array("lifetime" => CACHE_MAX_LIFETIME));
            $cache = new \Phalcon\Cache\Backend\File($frontCache,
                array('cacheDir' => $this->cachePath));

            $this->config = $cache->get('config');
            if ($this->config === null){
                $this->getConfig($this->configPath, $this->configTypes);
                $cache->save('config', $this->config);
            }
        }
    }

    private function getConfig($configPath, $configTypes){
        $this->config = Builder::factory(Builder::ADAPTER_JSON, $configPath . '/base.config.json');
        foreach ($configTypes as $config_type){
            $configName = $configPath . "/" . $config_type . "." . $this->mode . '.config.json';
            if (file_exists($configName)){
                $this->config->merge(Builder::factory(Builder::ADAPTER_JSON, $configName));
            }
        }
    }
    /**
     * Detects debug mode by IP address.
     * @param  string|array  IP addresses or computer names whitelist detection
     * @return bool
     */
    public function detectDebugMode($list = NULL){
        $list = $list !== null ? $list : $this->addresses;
        $list = is_string($list) ? preg_split('#[,\s]+#', $list) : $list;
        $list[] = '127.0.0.1';
        $list[] = '::1';
        return in_array(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : php_uname('n'), $list, TRUE);
    }

    public function isDebug(){
        return $this->debug;
    }

    public function isProduction(){
        return $this->production;
    }

    public function isDevelopment(){
        return $this->development;
    }

    public function getConfiguration(){
        return $this->config;
    }

    public function isTestingCache(){
        return $this->testCache;
    }

    public function setConfigTypes($configTypes)
    {
        $this->configTypes = $configTypes;
    }

    public function getConfigTypes()
    {
        return $this->configTypes;
    }

    public function setAddresses($addresses)
    {
        $this->addresses = $addresses;
    }

    public function getAddresses()
    {
        return $this->addresses;
    }

    public function setEnvironments($environments)
    {
        $this->environments = $environments;
    }

    public function getEnvironments()
    {
        return $this->environments;
    }


}