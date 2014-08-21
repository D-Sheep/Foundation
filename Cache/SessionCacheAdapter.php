<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 20/08/14
 */

namespace Foundation\Cache;


class SessionCacheAdapter implements \Phalcon\Session\AdapterInterface {

    const SESSION_COOKIE_KEY = 'SSID_sto';

    private $_data;

    private $session_id;

    private $is_started;

    /** @var \Phalcon\Cache\Backend */
    private $cache;

    function __construct($options = null)
    {
        $this->is_started = false;
        $di = \Phalcon\DI::getDefault();
        $dispatcher = $di->getDispatcher();

        $eventsManager = $di->getShared('eventsManager');
        $eventsManager->attach('dispatch', function($event, $dispatcher) use ($di) {
            if ($event->getType() == 'afterDispatch') {
                $session = $di->getSession();
                //$logger = $di->getLogger();
                //$logger->notice("jdu ukladat session");
                $session->__destruct();
                //$logger->commit();
                //$logger->close();

            }
        });
        $dispatcher->setEventsManager($eventsManager);
    }

    public function __destruct(){

        if ($this->_data == null ){
            return;
        }
        foreach ($this->_data as $key => $value) {
            if ($value!=null) {
                $this->cache->save($key,$value);
            }

        }
        $this->_data = null;
    }

    private function getSessionId(){
        if ($this->session_id == null){
            if (isset($_COOKIE[self::SESSION_COOKIE_KEY])){
                $this->session_id = $_COOKIE[self::SESSION_COOKIE_KEY];
            } else {
                $this->session_id = $this->generateSSID();
                setcookie(self::SESSION_COOKIE_KEY, $this->session_id);
            }

        }
        return $this->session_id;
    }

    private function generateSSID(){
        return bin2hex(openssl_random_pseudo_bytes(64));
    }

    private function getName($index){
        return $this->getSessionId().urlencode($index);
    }



    /**
     * Starts session, optionally using an adapter
     *
     * @param array $options
     */
    public function start()
    {
        $this->is_started = true;
        $di = \Phalcon\DI::getDefault();
        $cacheFactory = $di->getCacheFactory();
        $this->cache = $cacheFactory->getCacheBackend("s_");


    }

    /**
     * Sets session options
     *
     * @param array $options
     */
    public function setOptions($options)
    {

    }

    /**
     * Get internal options
     *
     * @return array
     */
    public function getOptions()
    {
        $this->cache->getOptions();
    }

    /**
     * Gets a session variable from an application context
     *
     * @param string $index
     * @param mixed $defaultValue
     * @return mixed
     */
    public function get($index, $defaultValue = null)
    {
        if (!$this->isStarted()) {
            $this->start();
        }
        $name = $this->getName($index);
        if (!isset($this->_data[$name])){
            $this->_data[$name] = $this->cache->get($name);
        }
        return $this->_data[$name];

    }

    /**
     * Sets a session variable in an application context
     *
     * @param string $index
     * @param string $value
     */
    public function set($index, $value)
    {
        if (!$this->isStarted()) {
            $this->start();
        }
        $name = $this->getName($index);
        $this->_data[$name] = $value;
    }

    /**
     * Check whether a session variable is set in an application context
     *
     * @param string $index
     * @return boolean
     */
    public function has($index)
    {
        $name = $this->getName($index);
        if (isset($this->_data[$name])){
            return true;
        }
        return $this->cache->exists($name);
    }

    /**
     * Removes a session variable from an application context
     *
     * @param string $index
     */
    public function remove($index)
    {
        $name = $this->getName($index);
        if (isset($this->_data[$name])){
            unset($this->_data[$name]);
        }
        $this->cache->delete($name);
    }

    /**
     * Returns active session id
     *
     * @return string
     */
    public function getId()
    {
        $this->session_id;

    }

    /**
     * Check whether the session has been started
     *
     * @return boolean
     */
    public function isStarted()
    {
        return $this->is_started;
    }

    /**
     * Destroys the active session
     *
     * @return boolean
     */
    public function destroy($session_id = null)
    {
        $this->is_started = false;
    }

}