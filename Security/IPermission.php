<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 17/07/14
 */

namespace Foundation\Security;

use model\Entities\AccountResource;

interface IPermission {

    //podle toho, co umi Phalcon model

    /* @return AccntResource */
    public function getResource();

    /* @return Role */
    public function getRole();

    /* @return String */
    public function getAccess();

    /* @return boolean */
    public function isAllowed();
}