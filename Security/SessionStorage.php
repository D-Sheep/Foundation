<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 25/07/14
 */

namespace Foundation\Security;

use Nette\Security\Security\IIdentity;
use Nette\Security\Security\IUserStorage;

class SessionStorage implements IUserStorage {

    const AUTHENTICATED = 'user_authenticated';
    const IDENTITY = 'user_identity';

    /** @var \Phalcon\DI\FactoryDefault */
    private $di;

    public function __construct(\Phalcon\DI\FactoryDefault $di){
        $this->di = $di;
    }

    /**
     * Sets the authenticated status of this user.
     * @param  bool
     * @return void
     */
    function setAuthenticated($state)
    {
        $this->di->getSession()->set(self::AUTHENTICATED, $state);
    }

    /**
     * Is this user authenticated?
     * @return bool
     */
    function isAuthenticated()
    {
        if (!$this->di->getSession()->has(self::AUTHENTICATED)){
            return false;
        }
        return $this->di->getSession()->get(self::AUTHENTICATED);
    }

    /**
     * Returns current user identity, if any.
     * @return \Nette\Security\Security\IIdentity|NULL
     */
    function getIdentity()
    {
        if (!$this->di->getSession()->has(self::IDENTITY)){
            return null;
        }
        return $this->di->getSession()->get(self::IDENTITY);
    }

    /**
     * Enables log out from the persistent storage after inactivity.
     * @param  string|int|DateTime number of seconds or timestamp
     * @param  int Log out when the browser is closed | Clear the identity from persistent storage?
     * @return void
     */
    function setExpiration($time, $flags = 0)
    {
        // TODO: Implement setExpiration() method.
    }

    /**
     * Why was user logged out?
     * @return int
     */
    function getLogoutReason()
    {
        // TODO: Implement getLogoutReason() method.
    }

    /**
     * Sets the user identity.
     * @return void
     */
    function setIdentity(IIdentity $identity = NULL)
    {
        $this->di->getSession()->set(self::IDENTITY, $identity);
    }
}