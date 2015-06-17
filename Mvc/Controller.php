<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 24/05/14
 * Time: 17:32
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Mvc;

use Foundation\Mvc\Router\LangRouter;

/**
 * Class Controller
 * @package Foundation\Mvc
 *
 * @method \Foundation\DI\Factory getDi() getDi()
 * @property \Foundation\DI\Factory $di
 *
 */
class Controller extends \Phalcon\Mvc\Controller {

    const DESCRIPTION_LENGTH = 160;

    protected $_langAlternatives = null;

    protected function initialize() {

        $this->getDi()->getEventsManager()->attach('view:beforeRender', $this);
    }

    protected function beforeRender() {
        $lang = $this->getDi()->getLang()->getUserDefaultLanguage();
        $this->view->lang = $lang;
        $this->view->basePath = $this->getDi()->getUrl()->getBasePath();
        $this->view->basePathWithLang = $this->getDi()->getUrl()->getBasePath().$lang."/";
        $this->view->hostUrl = $this->getDi()->getSuperUrl()->getHostUrl();
        $this->view->baseUrl = $this->getDi()->getSuperUrl()->getBaseUrl();
        $this->view->baseUrlWithLang = $this->getDi()->getSuperUrl()->getBaseUrl().$lang."/";

        $stubs = (array) $this->getDi()->getConfigurator()->getConfiguration()->application->forbiddenUrlStubs;
        $stubs = \Nette\Utils\Json::encode($stubs);
        $this->view->_forbiddenUrlStubs = $stubs;

        //links are arrays. urls are created from them in volt template.
        //It's because these arrays can by used for redirecting in phalcon.
        $this->view->_langAlternatives = $this->getAlternativeLinksForMeta();
    }

    /**
     * @param $ogTitle
     * @param $ogDescription
     * @param null $ogImage
     * @param null $ogType
     * @param array $ogArray
     */
    public function setFacebookOg($ogTitle, $ogDescription, $ogImage = null, $ogType = null, array $ogArray = null) {
        $this->view->_ogTitle = $ogTitle;
        if ($ogDescription) {
            if (strlen($ogDescription) > self::DESCRIPTION_LENGTH) $ogDescription = substr($ogDescription, 0, self::DESCRIPTION_LENGTH - 3) . "...";
            $this->view->_ogDescription = $ogDescription;
            $this->view->_twDesc = $ogDescription;
        }
        if ($ogImage) $this->view->_ogImage = $ogImage;
        if ($ogType) $this->view->_ogType = $ogType;
        $this->view->_ogArray = $ogArray ? $ogArray : array();
    }

    /*
     * string $description
     */
    public function setDescription($descriptoon){
        $this->view->_description = $descriptoon;
    }

    protected function getAlternativeLinksForMeta(){
        $route = $this->router->getMatchedRoute();
        if(isset($route) && $this->_langAlternatives === null) {
            $_langAlternatives = [];
            $langService = $this->getDi()->getLang();
            foreach ($langService->getAvailableLangs() as $lang) {
                $url = [
                    LangRouter::LANG_PARAM => $lang,
                    'for' => 'this',
                    'type' => 'no_query'
                ];
                $_langAlternatives[$lang] = $url;
            }
            $this->_langAlternatives = $_langAlternatives;
        }
        return $this->_langAlternatives;
    }

    /**
     * Gets alternative array for creating url by urlservice for specificated language
     * @param $lang
     * @return array|null
     */
    public function getAlternativeLinkForLang($lang){
        $langAlternatives = $this->getAlternativeLinksForMeta();
        return isset($langAlternatives[$lang]) ? $langAlternatives[$lang] : null;
    }


}