<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 06/08/14
 * Time: 16:20
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\View\Engine;


use Phalcon\Mvc\ViewInterface;

class MustachePartialsLoader implements \Mustache_Loader {

    /**
     * @var \Phalcon\Mvc\ViewInterface
     */
    protected $viewsDir;

    /**
     * @var Mustache
     */
    protected $mustache;

    function __construct(ViewInterface $view, Mustache $mustache)
    {
        $this->viewsDir = $view->getViewsDir();
        $this->mustache = $mustache;
    }


    /**
     * Load a Template by name.
     *
     * @throws Mustache_Exception_UnknownTemplateException If a template file is not found.
     *
     * @param string $name
     *
     * @return string Mustache Template source
     */
    public function load($name, $stache = false)
    {
        $m = null;
        if (preg_match("#([a-z0-9]+/[a-z0-9]+)$#i", $name, $m)) {
            $name = $m[1];
        }
        return $this->mustache->getCachedTemplate($this->viewsDir . $name . '.mustache', $stache);
    }


}