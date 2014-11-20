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
use Phalcon\DI\InjectionAwareInterface;
use Phalcon\DiInterface;

/**
 * Phalcon\Mvc\View\Engine\Mustache
 * Adapter to use Mustache library as templating engine
 */
class Mustache extends Engine implements EngineInterface, InjectionAwareInterface
{

    const STACHE = "stache";
    const MUSTACHE = "mustache";

    /**
     * @var \Mustache_Engine
     */
    protected $mustache;

    /*
     * Phalcon\DiInterface
     */
    protected $_di;

    /**
     * @param DiInterface $di
     */
    public function setDi($di)
    {
        $this->_di = $di;
    }

    /**
     * @return DiInterface
     */
    public function getDi()
    {
        return $this->_di;
    }

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
            $this->setDi($dependencyInjector);
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
    private function solveIfElseCompatibility($text){
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
        }, $text);

        if (sizeof($lifo)>0){
            throw new Exception("Bad sytax");
        }
        return $validatedText;
    }

    private function solveTranslations($text, $lang){
        $pattern = '/{_[\'"]([^{}"\']+)[\'"]}/';
        preg_match_all($pattern, $text, $match_all);
        if (isset($match_all[1]) && sizeof($match_all[1])>0) {
            $langService = $this->getDi()->getLang();
            $translations = $langService->translate($match_all[1], $lang);
            $i = -1;
            return preg_replace_callback($pattern, function($match_replace) use (&$i, $translations){
                $i++;
                return $translations[$i];
            }, $text);
        } else {
            return $text;
        }
    }

    /**
     * @param $path
     * @param bool $stache
     * @return mixed|string
     */
    public function getCachedTemplate($path, $stache = false) {
        $isMatched = preg_match("/(\w+)\/(\w+).mustache$/", $path, $match_all);

        if ($isMatched === false){
            new Exception("Path doesn't match format");
        }
        $basePath = realpath(APP_DIR ."/../public/");
        $lang = $this->getDi()->getLang()->getUserDefaultLanguage();
        $folder = $match_all[1];
        $filename = $match_all[2].($stache ? ".stache" : ".mustache");

        $cachedPath = $basePath."/".$lang."/".$folder."/".$filename;

        try {
            if (file_exists($cachedPath) && filemtime($cachedPath)>filemtime($path)){
                Logger::debug("login", "cached ".$cachedPath);
                return file_get_contents($cachedPath);
            } else {
                $content = file_get_contents($path);
                // stache
                if ($stache) {
                    $res = preg_replace('/[\s]+/', ' ', $content);
                    $res = preg_replace('/{{\s?([^\s\|]+)\s?\|\s?([^\s}]+)\s?}}/i', '{{\\2 \\1}}', $res);

                // mustache
                } else {
                    $res = $this->solveCompatibility($content, "each"); // each
                    $res = $this->solveIfElseCompatibility($res); // if else
                }
                // translation for lang
                $res = $this->solveTranslations($res, $lang);
                //save to file
                $this->createCachedTemplate($basePath, $lang, $folder, $filename, $res);
                Logger::debug("login", "generated ".$cachedPath);
                return $res;
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function createCachedTemplate($basePath, $lang, $folder, $filename, $data){
        try {
            $langDir = $basePath . "/" . $lang;
            if (!file_exists($langDir)) {
                mkdir($langDir, 0777, true);
            }
            $folderDir = $langDir . '/' . $folder;
            if (!file_exists($folderDir)) {
                mkdir($folderDir, 0777, true);
            }
            file_put_contents($folderDir."/".$filename, $data);
        } catch (\Exception $e) {
            throw new Exception("Path doesn't match format");
        }
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
