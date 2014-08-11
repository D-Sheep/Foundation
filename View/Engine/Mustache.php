<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 06/08/14
 * Time: 16:20
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\View\Engine;

use Phalcon\Mvc\View\Engine;
use Phalcon\Mvc\View\EngineInterface;

/**
 * Phalcon\Mvc\View\Engine\Mustache
 * Adapter to use Mustache library as templating engine
 */
class Mustache extends Engine implements EngineInterface
{

    /**
     * @var \Mustache_Engine
     */
    protected $mustache;

    /**
     * Class constructor.
     *
     * @param \Phalcon\Mvc\ViewInterface $view
     * @param \Phalcon\DiInterface       $dependencyInjector
     */
    public function __construct($view, $dependencyInjector = null)
    {
        $this->mustache = new \Mustache_Engine();
        $loader = new MustachePartialsLoader($view, $this);
        $this->mustache->setPartialsLoader($loader);

        parent::__construct($view, $dependencyInjector);
    }

    /**
     * {@inheritdoc}
     *
     * @param string  $path
     * @param array   $params
     * @param boolean $mustClean
     */
    public function render($path, $params, $mustClean = false)
    {
        if (!isset($params['content'])) {
            $params['content'] = $this->_view->getContent();
        }

        $content = $this->mustache->render($this->getCachedTemplate($path), $params);

        if ($mustClean) {
            $this->_view->setContent($content);
        } else {
            echo $content;
        }
    }



    /**
     * @param $path
     * @return mixed
     */
    public function getCachedTemplate($path) {
        return preg_replace('/[\s]+/', ' ', file_get_contents($path));
    }

    public function getPartial($path) {
        return $this->mustache->getPartialsLoader()->load($path);
    }

}
