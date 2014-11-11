<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 06/08/14
 * Time: 16:20
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\View\Engine;

use Foundation\Logger;
use Phalcon\Mvc\View\Engine;
use Phalcon\Mvc\View\EngineInterface;
use Phalcon\Mvc\View\Exception;

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

    /*
     * Phalcon\DiInterface
     */
    protected $_di;

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
        if ($dependencyInjector !== null) {
            $this->_di = $dependencyInjector;
        }

        $this->mustache->addHelper('uppercase', function($value) {
            return strtoupper((string) $value);
        });

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

        $tplContent = $this->getCachedTemplate($path);

        $content = $this->mustache->render("{{%FILTERS}}\n".$tplContent, $params);

        if ($mustClean) {
            $this->_view->setContent($content);
        } else {
            echo $content;
        }
    }

    /**
     * @param $t input text
     * @param $what tag, for example each
     * @return string
     * @throws \Phalcon\Mvc\View\Exception
     */
    private function solveCompatibility($t, $what){
        $pattern = '/{(#|\/)'.$what.'\s*([\w.]+\s*|)}/';
        $lifo = array();

        $validatedText = preg_replace_callback($pattern, function($matches) use ( &$lifo){

            if ($matches[1] === '#' ) {
                array_push($lifo, $matches[2]);
                return '{#'.$matches[2].'}';

            } else if ($matches[1] === '/') {
                if (sizeof($lifo) < 1){
                    throw new Exception("Bad sytax");
                }
                return '{/'.array_pop($lifo).'}';
            }
        }, $t);

        if (sizeof($lifo)>0){
            throw new Exception("Bad sytax");
        }
        return $validatedText;
    }

    /**
     * @param $t input text
     * @return string
     * @throws \Phalcon\Mvc\View\Exception
     */
    private function solveIfElseCompatibility($t){
        $pattern = '/{(#|\/)if\s*([\w.]+\s*|)}|{\s*else\s*}/';
        $lifo = array();

        $validatedText = preg_replace_callback($pattern, function($matches) use ( &$lifo){

            if (!(sizeof($matches)>1) ){ //else
                if (sizeof($lifo) < 1){
                    throw new Exception("Bad sytax");
                }
                $name = $lifo[sizeof($lifo)-1];
                $toOutput = '{/'.$name.'}}'.'{{^'.$name.'}';
                return $toOutput;
            } else if ($matches[1] === '#' ) {
                array_push($lifo, $matches[2]);
                return '{#'.$matches[2].'}';

            } else if ($matches[1] === '/') {
                if (sizeof($lifo) < 1){
                    throw new Exception("Bad sytax");
                }
                return '{/'.array_pop($lifo).'}';
            }
        }, $t);

        if (sizeof($lifo)>0){
            throw new Exception("Bad sytax");
        }
        return $validatedText;
    }

    /**
     * @param $path
     * @param bool $stache
     * @return mixed|string
     */
    public function getCachedTemplate($path, $stache = false) {
        $content = file_get_contents($path);

        // pro kazdej jazyk + jazykovou mutaci
        if ($stache) {
            $res = preg_replace('/[\s]+/', ' ', $content);
            $res = preg_replace('/{{\s?([^\s\|]+)\s?\|\s?([^\s}]+)\s?}}/i', '{{\\2 \\1}}', $res);
        } else {
            $res = $this->solveCompatibility($content, "each"); // each
            $res = $this->solveIfElseCompatibility($res); // if else
        }

        return $res;
    }


    public function callback($str) {
        if (is_array($str)) {
            $str = "{{# ".$str[1]."}}".$str[2]."{{/".$str[1]."}}";
        }
        return preg_replace_callback("|{{\s?#each ([^}]+)}}(.+)({{\s?/each\s?}})|i", [$this, "callback"], $str);
    }



    public function getPartial($path, $stache = false) {
        return $this->mustache->getPartialsLoader()->load($path, $stache);
    }

    /**
     * @return \Mustache_Engine
     */
    public function getMustacheEngine()
    {
        return $this->mustache;
    }



}
