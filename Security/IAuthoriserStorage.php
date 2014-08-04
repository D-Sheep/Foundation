<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 17/07/14
 */

namespace Foundation\Security;


interface IAuthoriserStorage {
    public function getAllPermissions();
    public function createAccountResource($name);
    public function findAccountResource($parameters);
    public function createAccountGroupPermission(
        $resource,$role, $access);
}