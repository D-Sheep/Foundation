<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 29/07/14
 * Time: 09:07
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Localization;


use Phalcon\Session\AdapterInterface;

class LangService {

    const STORED_SESSION_LANG = 'lang::savedLang';

    const LANG_CZ = 'cz';
    const LANG_EN = 'en';
    const LANG_DE = 'de';
    const LANG_NE = 'nl';
    const LANG_FR = 'fr';

    private $_cachedLang;

    /**
     * @var \Phalcon\Session\AdapterInterface|\Phalcon\Session\Adapter
     */
    protected $session;

    function __construct(AdapterInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @return string
     */
    public function getUserDefaultLanguage() {
        if ($this->_cachedLang === null) {
            if ($this->getLoggedIdentity() !== null) {
                //@todo implement user language
            } else if ($this->session->get(self::STORED_SESSION_LANG) === null) {
                $lang = $this->decideUserDefaultLanguage();
                $this->session->set(self::STORED_SESSION_LANG, $lang);
                $this->_cachedLang =  $lang;
            } else {
                $this->_cachedLang =  $this->session->get(self::STORED_SESSION_LANG);
            }
        }
        return $this->_cachedLang;
    }

    public function isMatchingUserDefaultLanguage($lang) {
        return strtolower($lang) === $this->getUserDefaultLanguage();
    }

    protected function decideUserDefaultLanguage() {
        // @todo implement IP locator
        return self::LANG_CZ;
    }

    protected function getLoggedIdentity() {
        return null;
    }

}