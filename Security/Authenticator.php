<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 17/07/14
 */

namespace Foundation\Security;

use Nette\Security\Security\IAuthenticator,
    Storyous\Entities\Account;

class Authenticator implements IAuthenticator {

    var $passwordField;
    var $saltField;

    /** @var IAuthenticatorStorage */
    var $authenticatorStorage;

    public static $account_not_exists = "User account not exists";
    public static $password_mismatch  = "User password mismatch";
    public static $account_disabled  = "User account is disabled";
    public static $account_hasnt_password = "Account is connected over social network";

    const ACCOUNT_TYPE_SYSTEM = 1;
    const ACCOUNT_TYPE_INDIRECT = 2;

    public static $account_types = array(
        self::ACCOUNT_TYPE_SYSTEM => "System account",
        self::ACCOUNT_TYPE_INDIRECT => "Indirect authorized"
    );

    const ERR_ACCOUNT_NOT_EXSTS = 100;
    const ERR_PASSWORD_MISMATCH = 101;
    const ERR_DISABLED_ACCOUNT  = 102;
    const ERR_NO_PASSWORD_ACCOUNT = 103;

    const PRE_SHA1 = 'pre_sha';


    public function __construct(IAuthenticatorStorage $storage){
        $this->authenticatorStorage = $storage;
    }

    function authenticate(array $credentials){
        //$logger = \Phalcon\DI::getDefault()->getLogger();
        $person = $this->authenticatorStorage->getIdentityByName($credentials[self::USERNAME]);
        //$logger->alert(var_export($person->getAccount(),true));
        if (count($person) == 0) {
            throw new \Nette\Security\Security\AuthenticationException(self::$account_not_exists, self::ERR_ACCOUNT_NOT_EXSTS);
        }

        $acc = $person->getAccount();
        /* @var $acc Account */

        if ($acc->password == null) {
            throw new \Nette\Security\Security\AuthenticationException(self::$account_hasnt_password, self::ERR_NO_PASSWORD_ACCOUNT);
        }

        if ((isset($acc->enabled) && !$acc->enabled) || (isset($acc->account_type) && \in_array($acc->account_type, array(
            self::ACCOUNT_TYPE_SYSTEM
        )))) {
            throw new \Nette\Security\Security\AuthenticationException(self::$account_disabled, self::ERR_DISABLED_ACCOUNT);
        }
        $hashed =  Account::hashPassword($acc->email, $credentials[self::PASSWORD], $acc->salt, isset($credentials[self::PRE_SHA1]) ? !!$credentials[self::PRE_SHA1] : false);
        if ($acc->password !== $hashed) {
            throw new \Nette\Security\Security\AuthenticationException(self::$password_mismatch, self::ERR_PASSWORD_MISMATCH);
        }

        if (!$acc->salt) {
            $acc->updatePassword($credentials[self::PASSWORD]);
            $acc->update(["salt", "password"]);
        }
        //$logger->alert(var_export($person,true));
        return $person;
    }
}