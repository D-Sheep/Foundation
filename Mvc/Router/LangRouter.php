<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 29/07/14
 * Time: 09:14
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Mvc\Router;


use Foundation\Localization\ILangService;
use Foundation\Mvc\Dispatcher;
use Phalcon\Events\Event;
use Phalcon\Mvc\Router;

class LangRouter extends Router {

    const LANG_PARAM = 'lang';
    const ALIAS_PARAM = 'alias';

    /**
     * @var \Foundation\Localization\ILangService
     */
    protected $lang;

    public function __construct($defaultRoutes = null, ILangService $lang = null)
    {
        $this->lang = $lang;
        parent::__construct($defaultRoutes);
    }


    public function beforeDispatch(Event $event, Dispatcher $dispatcher) {

    }

    public function beforeDispatchLoop(Event $event, Dispatcher $dispatcher) {
        $lang = $dispatcher->getParam(self::LANG_PARAM);
        $route = $dispatcher->getDI()->getRouter()->getMatchedRoute();

        if ($route !== null) {
            $paths = $route->getPaths();
            $isLanguageRoute = array_key_exists(self::LANG_PARAM, $paths);
            $isAliasRoute = array_key_exists(self::ALIAS_PARAM, $paths);
        } else {
            $isLanguageRoute = false;
        }

        if ($isLanguageRoute && $isAliasRoute)
        {
            $aliases = $this->lang->getAliases($lang);
            $alias = $dispatcher->getParam(self::ALIAS_PARAM);
            if ($alias && isset($aliases[$alias]))
            {
                $dispatcher->setControllerName($aliases[$alias]['controller']);
                $dispatcher->setActionName($aliases[$alias]['action']);
            }
        }
        else
        if ($isLanguageRoute
                && !$this->isVisitedByRobot()
                && !$this->lang->isMatchingUserDefaultLanguage($lang)) {
            $dispatcher->setParam('lang', 'cz');
            $this->getDI()->getResponse()->redirect([
                self::LANG_PARAM => 'cz',
                'for' => 'index',
            ])->send();
        }
    }

    public function isVisitedByRobot() {
        $userAgent =  $this->getDI()->getRequest()->getHeader('User-agent');
        return preg_match("/(bot|facebook|googlebot|facebookexternalhit|twitterbot|crawler)/i", $userAgent);
    }

    public function getRouteByName($name)
    {
        if ($name === 'this') {
            return $this->getMatchedRoute();
        } else {
            return parent::getRouteByName($name);
        }
    }


}