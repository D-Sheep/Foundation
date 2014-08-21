<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 25/07/14
 */

namespace Foundation\Security;

use Nette\Security\Security\IIdentity;
use Nette\Security\Security\IUserStorage;

class MemoryStorage  implements IUserStorage {

    protected $identity = null;
    protected $authenticated = false;

    function __construct(IIdentity $identity = null, $authenticated = null) {
        $this->identity = $identity;
        $this->authenticated = $authenticated !== null ? $authenticated : !!$identity;
    }

    /**
     * Sets the authenticated status of this user.
     * @param  bool
     * @return void
     */
    function setAuthenticated($state){
        $this->authenticated = $state;
    }

    /**
     * Is this user authenticated?
     * @return bool
     */
    function isAuthenticated(){
        return $this->authenticated;
    }

    /**
     * Sets the user identity.
     * @return void
     */
    function setIdentity(IIdentity $identity = NULL){
        $this->identity = $identity;
    }

    /**
     * Returns current user identity, if any.
     * @return \Nette\Security\Security\IIdentity|NULL
     */
    function getIdentity(){
        return $this->identity;
    }

    /**
     * Enables log out from the persistent storage after inactivity.
     * @param  string|int|DateTime number of seconds or timestamp
     * @param  int Log out when the browser is closed | Clear the identity from persistent storage?
     * @return void
     */
    function setExpiration($time, $flags = 0){}

    /**
     * Why was user logged out?
     * @return int
     */
    function getLogoutReason(){}
}