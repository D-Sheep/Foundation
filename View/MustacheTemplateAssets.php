<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 30/07/14
 * Time: 10:39
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\View;


use Foundation\View\Engine\Mustache;
use Nette\Utils\Finder;
use Phalcon\Cache\Backend;
use Phalcon\Mvc\View;

class MustacheTemplateAssets {

    /**
     * @var \Phalcon\Mvc\View
     */
    protected $view;

    /**
     * @var \Phalcon\Cache\Backend
     */
    protected $cache;

    /**
     * @var \Foundation\View\Engine\Mustache
     */
    private $_mustache;

    private $_di;

    //@todo cache neni potřeba?!
    function __construct(Backend $cache, View $view, $di = null) {
        $this->cache = $cache;
        $this->view = $view;
        $this->_di = $di;
    }

    /**
     * @param $lang
     * @return array
     */
    public function getFormattedTemplateAsset() {
        return $this->getAssetsArray();
    }

    protected function getAssetsArray() {
        $array = [];

        foreach ($this->getFiles() as $file) {
            /* @var $file \SplFileInfo */
            $dir = str_replace([$this->view->getViewsDir(), ".mustache"], ["", ""], $file->getRealPath());
            $dir = strtolower($dir);
            $ex = explode("/", $dir);
            if (count($ex) === 2 && $ex[0] === $ex[1]) {
                $dir = $ex[0] . "/default";
            }
            $array[] = (object) [
                'path' => $dir,
                'template' => $this->getTemplateContents($file),
            ];
        }
        return $array;
    }

    protected function getFiles() {
        return Finder::findFiles('*.mustache')->from($this->view->getViewsDir());
    }

    protected function getTemplateContents(\SplFileInfo $file) {
        return $this->getMustache()->getCachedTemplate((string) $file, true);
    }

    /**
     * @return \Foundation\View\Engine\Mustache
     */
    protected function getMustache() {
        if ($this->_mustache === null) {
            $this->_mustache = new Mustache($this->view, $this->_di);
        }
        return $this->_mustache;
    }

}