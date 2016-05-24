<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 29/07/14
 * Time: 15:54
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Mvc;

use Foundation\Logger;
use Foundation\Mvc\Router\LangRouter;

class Url extends \Phalcon\Mvc\Url {

    /**
     * @param null $uri
     * @param null $args
     * @return string
     */
    public function get($uri = null, $args = null, $local = null)
    {
        if (isset($uri['for'])) {
            $lang = null;
            if (isset($uri[LangRouter::LANG_PARAM])) $lang = $uri[LangRouter::LANG_PARAM];
            $route = $this->getDI()->getRouter()->getRouteByName($uri['for'], $lang);
            if ($route === null){
                return "notfound";
            } else {
                $paths = $route->getPaths();
                $request = $this->getDI()->getRequest();
                $dispatcher = $this->getDI()->getDispatcher();

                foreach ($paths as $pathKey => $value) {
                    if (!array_key_exists($pathKey, $uri) && $pathKey !== 'for' && $pathKey) {
                        $uri[$pathKey] = $dispatcher->getParam($pathKey) ?: $value;
                    }
                }

                // if query args should be included
                if (!isset($uri['type']) || $uri['type'] !== 'no_query'){
                    if ($args === null) {
                        $args = [];
                    }

                    if ($uri['for'] === 'this') {
                        foreach ($request->get() as $param => $value) {
                            if (!array_key_exists($param, $args) && $param !== '_url' && $param !== 'setLang') {
                                $args[$param] = $value;
                            }
                        }
                    }
                }



                if ($lang !== null) {
                    $uri["for"] = $route->getName();
                }
            }

        }

        return parent::get($uri, $args);
    }
}