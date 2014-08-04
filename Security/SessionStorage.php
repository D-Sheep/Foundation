<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 25/07/14
 */

namespace Foundation\Security;

use Nette\Security\Security\IUserStorage;

class SessionStorage implements IUserStorage {


    /**
     * Sets the authenticated status of this user.
     * @param  bool
     * @return void
     */
    function setAuthenticated($state)
    {
        // TODO: Implement setAuthenticated() method.
    }

    /**
     * Is this user authenticated?
     * @return bool
     */
    function isAuthenticated()
    {
        // TODO: Implement isAuthenticated() method.
    }

    /**
     * Sets the user identity.
     * @return void
     */
    function setIdentity(IIdentity $identity = NULL)
    {
        // TODO: Implement setIdentity() method.
    }

    /**
     * Returns current user identity, if any.
     * @return \Nette\Security\Security\IIdentity|NULL
     */
    function getIdentity()
    {
        // TODO: Implement getIdentity() method.
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
}