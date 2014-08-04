<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 17/07/14
 */

namespace Foundation\Security;

use Nette\Security\IAuthorizator;
use Phalcon\Acl\Adapter as Adapter;
use Storyous\AccountGroup;
use Storyous\AccountGroupPermission;
use model\Security\AuthoriserStorage;

class Authoriser extends Adapter implements IAuthorizator  {

    const SUPERUSER = 'superuser';
    const GUEST = 'guest';
    const SYSTEM = 'system';
    const INDIRECT = 'indirect';

    //private $groups;
    private $permissions;
    //private $role;

    /** @var IAuthoriserStorage */
    private $storage;

    function __construct(IAuthoriserStorage $storage) {
        $this->storage = $storage;
    }

    /**
     *
     * @return
     */
    protected function getPermissions($group = null, $resource = null, $privilege = null) {
        if (!$this->permissions) {
            $this->permissions = array();
            foreach ($this->storage->getAllPermissions() as $perm) {
                /* @var $perm AccountGroupPermission */
                $g = $perm->getAccountGroup()->code;
                $r = $perm->getAccountResource()->code;
                $this->addPermission($g, $r, $perm->permission, $perm->isAllowed);
            }
        }
        if ($group && $resource && $privilege) {
            return isset($this->permissions[$group], $this->permissions[$group][$resource], $this->permissions[$group][$resource][$privilege]) ?
                $this->permissions[$group][$resource][$privilege] : null;
        } else {
            return $this->permissions;
        }
    }

    protected function addPermission($group, $resource, $privilege, $state) {
        if (!isset($this->permissions[$group])) {
            $this->permissions[$group] = array();
        }
        if (!isset($this->permissions[$group][$resource])) {
            $this->permissions[$group][$resource] = array();
        }
        $this->permissions[$group][$resource][$privilege] = $state;
    }

    public function isAllowed($role, $resource, $access) {
        $p = $this->getPermissions($role, $resource, $access);
        if ($p === null) {
            // resource existence?
            $createResource = true;
            foreach ($this->permissions as $groupName => $resources) {
                foreach ($resources as $resName => $value) {
                    if ($resName == $resource) {
                        $createResource = false;
                        break;
                    }
                }
            }
            if ($createResource) {
                $res = $this->storage->createAccountResource($resource);
            } else {
                $res = $this->storage->findAccountResource(array('code~LIKE'=>$resource));
            }
            $perm = $this->storage->createAccountGroupPermission($res,$role,$access);
            $this->addPermission($role, $resource, $access, $perm->isAllowed);
            return $perm->isAllowed;
        }
        return $p;
    }

    /**
     * Adds a role to the ACL list. Second parameter lets to inherit access data from other existing role
     *
     * @param  \Phalcon\Acl\RoleInterface $role
     * @param  string $accessInherits
     * @return boolean
     */
    public function addRole($role, $accessInherits = null) {
        //TODO
    }

    /**
     * Return an array with every role registered in the list
     *
     * @return \Phalcon\Acl\RoleInterface[]
     */
    public function getRoles(){
        //TODO
    }

    /**
     * Return an array with every resource registered in the list
     *
     * @return \Phalcon\Acl\ResourceInterface[]
     */
    public function getResources(){
        // TODO: Implement getResources() method.
    }

    /**
     * Do a role inherit from another existing role
     *
     * @param string $roleName
     * @param string $roleToInherit
     */
    public function addInherit($roleName, $roleToInherit) {
        // TODO: Implement addInherit() method.
    }

    /**
     * Check whether role exist in the roles list
     *
     * @param  string $roleName
     * @return boolean
     */
    public function isRole($roleName) {
        // TODO: Implement isRole() method.
    }

    /**
     * Check whether resource exist in the resources list
     *
     * @param  string $resourceName
     * @return boolean
     */
    public function isResource($resourceName) {
        // TODO: Implement isResource() method.
    }

    /**
     * Adds a resource to the ACL list
     *
     * Access names can be a particular action, by example
     * search, update, delete, etc or a list of them
     *
     * @param   \Phalcon\Acl\ResourceInterface $resource
     * @param   array $accessList
     * @return  boolean
     */
    public function addResource($resource, $accessList = null) {
        // TODO: Implement addResource() method.
    }

    /**
     * Adds access to resources
     *
     * @param string $resourceName
     * @param mixed $accessList
     */
    public function addResourceAccess($resourceName, $accessList) {
        // TODO: Implement addResourceAccess() method.
    }

    /**
     * Removes an access from a resource
     *
     * @param string $resourceName
     * @param mixed $accessList
     */
    public function dropResourceAccess($resourceName, $accessList) {
        // TODO: Implement dropResourceAccess() method.
    }

    /**
     * Allow access to a role on a resource
     *
     * @param string $roleName
     * @param string $resourceName
     * @param mixed $access
     */
    public function allow($roleName, $resourceName, $access) {
        // TODO: Implement allow() method.
    }

    /**
     * Deny access to a role on a resource
     *
     * @param string $roleName
     * @param string $resourceName
     * @param mixed $access
     * @return boolean
     */
    public function deny($roleName, $resourceName, $access) {
        // TODO: Implement deny() method.
    }
}