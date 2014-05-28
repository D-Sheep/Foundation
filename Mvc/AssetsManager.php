<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 24/05/14
 * Time: 17:52
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Mvc;


use Foundation\Helper\Css\LessFilter;
use Phalcon\Assets\Filters\Cssmin;
use Phalcon\Assets\Manager;

class AssetsManager {

    const ASSETS_COLLECTION_HEADER = 'header';
    const ASSETS_COLLECTION_FOOTER = 'footer';

    /**
     * @var \Phalcon\Assets\Manager
     */
    protected $manager;

    function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param Controller $controller
     */
    public function initialize(Controller $controller) {

        $collection =  $this->manager->collection(self::ASSETS_COLLECTION_HEADER);

        $url = $controller->getDi()->getUrl();

        $basePath = $url->getBasePath();

        $filename = WWW_DIR . '/webloader/general.css';

        if (!file_exists($filename)) {
            $collection->setTargetPath($filename)
                ->setTargetUri('webloader/general.css')
                ->addCss($basePath . 'bootstrap/bootstrap.less')
                ->addCss($basePath . 'css/screen.css')
                ->addCss($basePath . 'css/style.css')
                ->addFilter(new LessFilter())
                ->addFilter(new Cssmin())
                ->join(true);
        } else {
            $collection->addCss($basePath . 'webloader/general.css');
        }


        $footer = $this->manager->collection(self::ASSETS_COLLECTION_FOOTER);

        $footer->addJs('js/jquery-1.11.1.min.js')
               ->addJs('js/bootstrap.min.js')
               ->addJs('js/base/js.js');

    }

}