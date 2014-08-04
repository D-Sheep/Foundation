<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 25/07/14
 */

namespace Foundation\Security;

use Nette\Security\IUserStorage;

class MemoryStorage  implements IUserStorage {
    /**
     * Sets the authenticated status of this user.
     * @param  bool
     * @return void
     */
    function setAuthenticated($state){}

    /**
     * Is this user authenticated?
     * @return bool
     */
    function isAuthenticated(){}

    /**
     * Sets the user identity.
     * @return void
     */
    function setIdentity(IIdentity $identity = NULL){}

    /**
     * Returns current user identity, if any.
     * @return \Nette\Security\Security\IIdentity|NULL
     */
    function getIdentity(){}

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