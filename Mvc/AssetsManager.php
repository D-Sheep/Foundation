<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 24/05/14
 * Time: 17:52
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Mvc;

use Foundation\Cache\CacheFactory,
    Foundation\Config\Configurator,
    Foundation\Logger,
    Phalcon\Assets\Manager,
    Phalcon\Assets\Collection,
    Foundation\Helper\Css\LessFilter,
    Phalcon\Assets\Filters\Cssmin,
    Phalcon\Assets\Filters\Jsmin;


class AssetsManager {

    const ASSETS_COLLECTION_HEADER = 'header';
    const ASSETS_COLLECTION_FOOTER = 'footer';
    const PROCESSING_CACHE = 'process';
    const DEFAULT_DESTINATION_FOLDER = "webloader";
    const SLEEP_DURATION = 70;

    /* @var Container */
    private $js;

    /* @var Container */
    private $css;

    /* @var String  */
    private $destinationFolder;

    /* @var String */
    private $destinationPath;

    /* $var String */
    private $resourceUrl;

    /* @var CacheFactory $cache  */
    private $cacheFactory;

    /* @var Configurator $configurator */
    private $configurator;

    /** @var Url */
    private $url;

    /*
     * Sets css and js used in whole application.
     * @param CacheFactory $cache
     * @param Configurator $configurator
     * @param Url $url
     * @param array $css default css folders
     * @param array $js default javascript folders
     */
    public function __construct($cacheFactory, $configurator, $url, $css = [], $js = []){
        $this->js = new Container($js);
        $this->css = new Container($css);
        $this->cacheFactory = $cacheFactory;
        $this->configurator = $configurator;
        $this->destinationFolder = self::DEFAULT_DESTINATION_FOLDER;
        $this->destinationPath = null;
        $this->resourceUrl = null;
        $this->url = $url;
    }

    /**
     * Initialize assets
     * @param \Phalcon\Assets $assets
     */
    public function initialize($assets){
        if($this->configurator->isDebug() && !$this->configurator->isTestingCache() ){
            $this->setCssAndJs($assets);
        //use as production
        } else {
            /* @var \Phalcon\Cache\Backend $cache */
            $cache = $this->cacheFactory->getCacheBackend('assets');

            //css
            $cacheName = $this->css->getName() . '+css';

            $collection = $assets->collection(self::ASSETS_COLLECTION_HEADER);

            $filename = $cache->get( $cacheName );

            if ($filename === null){
                $collection->addFilter(new LessFilter( "css"))
                            ->addFilter(new Cssmin())
                        ->join(true);
                $this->generateCollectionWithCache($collection, $cache, $cacheName, "css");
            } else {
                //waits until its generated
                while ( $filename == SELF::PROCESSING_CACHE ){
                    usleep(self::SLEEP_DURATION);
                    $filename = $this->cache->getCache( $cacheName );
                }
                $filepath = $this->destinationFolder . "/" . $filename;
                if (!file_exists($filepath)) {
                    $collection->addFilter(new LessFilter( "css"))
                                ->addFilter(new Cssmin())
                            ->join(true);
                    $this->generateCollectionWithCache($collection, $cache, $cacheName, "css");
                } else {
                    $collection->addCss($this->destinationFolder . "/" . $filename );
                }
            }

            //js
            $cacheName = $this->js->getName() . '+js';

            $collection = $assets->collection(self::ASSETS_COLLECTION_FOOTER);

            $filename = $cache->get( $cacheName );

            if ($filename === null){
                $collection->addFilter(new Jsmin());
                $this->generateCollectionWithCache($collection, $cache, $cacheName, "js");
                $this->applyFilters($collection, $this->js->getFilters());
            } else {
                //waits until its generated
                while ( $filename == SELF::PROCESSING_CACHE ){
                    usleep(self::SLEEP_DURATION);
                    $filename = $this->cache->getCache( $cacheName );
                }
                $filepath = $this->destinationFolder . "/" . $filename;
                if (!file_exists($filepath)) {
                    $collection->addFilter(new Jsmin());
                    $this->generateCollectionWithCache($collection, $cache, $cacheName, "js");
                    $this->applyFilters($collection, $this->js->getFilters());
                } else {
                    $collection->addCss($this->destinationFolder . "/" . $filename );
                }
            }

        }
    }

    private function generateCollectionWithCache($collection, $cache, $cacheName, $suffix){
        $cache->save($cacheName, self::PROCESSING_CACHE);

        $name = $this->getFilename($cacheName) . '.' . $suffix;
        $collection
            ->setTargetPath(WWW_DIR . "/" . $this->destinationFolder . "/" . $name)
            ->setTargetUri( $this->destinationFolder . "/" . $name . $this->getTimeHash())
            ->join(true);
        if($suffix== "css"){
            $this->generateContent($collection, $this->css->getFolders(),
                $suffix . "/", true);
        } else {
            $this->generateContent($collection, $this->js->getFolders(),
                $suffix . "/", false);
        }
        $cache->save($cacheName, $name);
    }

    private function setCssAndJs($assets){
        /* @var Manager $assets */

        //css
        $collection = $assets->collection(self::ASSETS_COLLECTION_HEADER);

        //set up to date
        if (file_exists(WWW_DIR . "/" . $this->destinationFolder . "/general.css")){
            $upToDate = $this->generateContent($collection, $this->css->getFolders(),
                "css/", true, true, WWW_DIR . "/" . $this->destinationFolder . "/general.css");
        } else {
            $upToDate = false;
        }

        if ($upToDate) {
            $collection->addCss( $this->destinationFolder . "/general.css");
        } else {
            $collection->addFilter(new LessFilter("css"))
                ->setTargetPath(WWW_DIR . "/" . $this->destinationFolder . "/general.css")
                ->setTargetUri( $this->destinationFolder . "/general.css" . $this->getTimeHash())
                ->join(true);
            $this->generateContent($collection, $this->css->getFolders(),
                "css/", true);
        }

        //js
        $collection = $assets->collection(self::ASSETS_COLLECTION_FOOTER);
        $collection = $this->generateContent($collection, $this->js->getFolders(),
            "js/", false);
        $this->applyFilters($collection, $this->js->getFilters());
    }

    /*
     * Generetes addCss/addJs of files to colection over $folders
     * @var Collection $collection Collection from assets
     * @var array $folders Strings of folder names
     * @var path $path Path to root folder of folders
     * @var boolean $css Indicates if css or js
     */
    private function generateContent( $collection, $folders, $path, $css, $upToDate = false, $generalPath = null){
        /* @var Collection $collection */
        foreach ($folders as $folder){
            $folderPath = $path . $folder;
            $subfolders[] = $folderPath;
            $array = [];
            while (sizeof($subfolders)>0){
                $folderPath = array_pop($subfolders);
                if (is_file($folderPath)){
                    $array[] = $folderPath;
                } else if ($handle = opendir($folderPath)) {
                    while (false !== ($file = readdir($handle))) {
                        if ($file == '.' || $file == '..') {
                            continue;
                        }
                        $file  = $folderPath  . "/" . $file;
                        if (is_file($file)){
                            $array[] = $file;
                        } else {
                            array_push($subfolders, $file);
                        }
                    }
                    closedir($handle);
                }
            }

            usort($array, function($left, $right){
                $m = null;
                $n = null;
                preg_match_all('/\//', $left, $m);
                preg_match_all('/\//', $right, $n);
                $slashCountLeft = count($m[0]);
                $slashCountRight = count($n[0]);

                if ($slashCountLeft == $slashCountRight) {
                    $array = [$left, $right];
                    sort($array);
                    return $array[0] == $left ? - 1 : 1;
                } else {
                    return $slashCountLeft - $slashCountRight;
                }
            });

            foreach($array as $item) {
                if ($css && preg_match('/\.(css|less)$/', $item)) { //css
                    if ($upToDate){
                        if (filemtime($item)>filemtime($generalPath)) {
                            return false;
                        }
                    } else {
                        $collection->addCss($item);
                    }
                } else if (!$css && preg_match('/\.js$/', $item)) { //js
                    $collection->addJs($item);
                }
            }

        }
        if ($upToDate){
            return true;
        }
        return $collection;
    }

    private function getTimeHash(){
        return '?t='.time();
    }

    private function applyFilters($collection, $filters){
        /* @var Collection $collection */
        foreach ($filters as $filter){
            $collection->addFilter($filter);
        }
        return $collection;
    }

    private function getFilename($foldername){
        return 'general' . hash('fnv164', $foldername);
    }

    public  function getCss(){
        return $this->css;
    }

    public function getJs(){
        return $this->js;
    }

    public function setDestinationFolder($df){
        $this->destinationFolder = $df;
    }

    public function setDestinationPath($dp){
        $this->destinationPath=$dp;
    }

    public function setResourceUrl($ru){
        $this->resourceUrl = $ru;
    }

    public function getDestinationPath(){
        if($this->destinationPath === null){
            return WWW_DIR;
        }
        return $this->destinationPath;
    }

    public function getResourceUrl(){
        if($this->resourceUrl === null){
            return $this->url->getStaticBaseUri();
        }
        return $this->resourceUrl;
    }

}