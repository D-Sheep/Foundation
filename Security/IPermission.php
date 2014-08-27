<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 17/07/14
 */

namespace Foundation\Security;

use Phalcon\Acl\ResourceInterface;
use Phalcon\Acl\RoleInterface;

interface IPermission {

    /* @return ResourceInterface */
    public function getResource();

    /* @return RoleInterface */
    public function getRole();

    /* @return String */
    public function getAccess();

    /* @return boolean */
    public function isAllowed();
}