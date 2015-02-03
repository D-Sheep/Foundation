<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 29/07/14
 * Time: 15:54
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Mvc;


class Url extends \Phalcon\Mvc\Url {

    /**
     * @param null $uri
     * @param null $args
     * @return string
     */
    public function get($uri = null, $args = null, $local = null)
    {
        if (isset($uri['for'])) {
            $route = $this->getDI()->getRouter()->getRouteByName($uri['for']);
            $paths = $route->getPaths();
            $request = $this->getDI()->getRequest();
            $dispatcher = $this->getDI()->getDispatcher();

            foreach($paths as $pathKey => $value) {
                if (!array_key_exists($pathKey, $uri) && $pathKey !== 'for') {
                    $uri[$pathKey] = $dispatcher->getParam($pathKey) ?: $value;
                }
            }


            if ($args === null) {
                $args = [];
            }
            if ($uri['for'] === 'this') {
                foreach($request->get() as $param => $value) {
                    if (!array_key_exists($param, $args) && $param !== '_url') {
                        $args[$param] = $value;
                    }
                }
            }

        }

        return parent::get($uri, $args);
    }
}