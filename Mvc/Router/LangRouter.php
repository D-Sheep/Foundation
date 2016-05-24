<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 29/07/14
 * Time: 09:14
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Mvc\Router;

use Foundation\Localization\ILangService;
use Foundation\Logger;
use Foundation\Mvc\Dispatcher;
use Phalcon\Events\Event;
use Phalcon\Mvc\Router;
use Storyous\Core\Lang\LangService;

class LangRouter extends Router {

	const LANG_PARAM = 'lang';
	const SET_LANG_IN_URL = "setLang";

	/**
	 * @var \Foundation\Localization\ILangService
	 */
	protected $lang;
	protected $translatedRoutes = [];

	public function __construct($defaultRoutes = null, ILangService $lang = null) {
		$this->lang = $lang;
		parent::__construct($defaultRoutes);
	}

    public function beforeDispatchLoop(Event $event, Dispatcher $dispatcher) {
        $url = $dispatcher->getDI()->getSuperUrl()->getPathInfo();//$dispatcher->getDI()->getSuperUrl()->getPath();
        preg_match("/(?'url'.*)\/$/", $url, $output_array);

        // if url ends with / redirect
        if (sizeof($output_array) > 0 && isset($output_array["url"])) {
            $newUrl = ($output_array["url"] == '' ? '/' : $output_array["url"]);
            $query = $dispatcher->getDI()->getSuperUrl()->getQuery();
            if ($query !== null && $query !== ''){
                $newUrl = $newUrl . "?". $query;
            }
            $dispatcher->getDI()->getResponse()->redirect($newUrl, null, 301);
        }

    }

	public function beforeExecuteRoute(Event $event, Dispatcher $dispatcher) {

		$lang = $dispatcher->getParam(self::LANG_PARAM);
		$route = $dispatcher->getDI()->getRouter()->getMatchedRoute();

        //is lang param in route?
        if ($route !== null) {
            $paths = $route->getPaths();
            $isLanguageRoute = array_key_exists(self::LANG_PARAM, $paths);
        } else {
            $isLanguageRoute = false;
        }

		$request = $dispatcher->getDI()->getRequest();
		$queryParams = $request->getQuery();
        //set new lang from URL
		if (isset($queryParams[self::SET_LANG_IN_URL]) && !$this->lang->isMatchingUserDefaultLanguage($queryParams[self::SET_LANG_IN_URL])){


            $newLang =$queryParams[self::SET_LANG_IN_URL];
            //set new user lang to session
            $session = $dispatcher->getDI()->getSession();
            $session->set(LangService::STORED_SESSION_LANG, $newLang);

            //return right content
            if ($isLanguageRoute) {
                $this->redirectToLang($dispatcher, $newLang);
            }
		}

        //redirect to user lang
		if ($isLanguageRoute
            && !$this->isVisitedByRobot()
			 && !$this->lang->isMatchingUserDefaultLanguage($lang)) {

            Logger::debug("localization", "----langRouter...");
			$langParam = $this->lang->getUserDefaultLanguage();
            Logger::debug("localization", "lang: $lang");
			$this->redirectToLang($dispatcher, $langParam);
		}
	}

	private function redirectToLang(Dispatcher $dispatcher, $newLang){
		//TODO je lang podporovaný? LangService->getAvailableLangs
		//co dělat když neni podporovanej?
		$controller = $dispatcher->getActiveController();

		if (method_exists($controller, "getAlternativeLinkForLang")){
			$arrayForLink = $controller->getAlternativeLinkForLang($newLang);
            if ($arrayForLink!== null) {
                Logger::debug("localization", "araryforlink:");
                Logger::debug("localization", $$arrayForLink);
                $dispatcher->setParam('lang', $newLang);
                $response = $this->getDI()->getResponse()->redirect($arrayForLink);
                $response->send();
                return;
            }
		}

        $dispatcher->setParam('lang', $newLang);
        $response = $this->getDI()->getResponse()->redirect([
            self::LANG_PARAM => $newLang,
            'for' => 'this'
        ]);
        $response->send();
        return;
		/*$headers = $response->getHeaders();
        $location = $headers->get("Location");
        $location = substr($location, 0, (strlen(self::SET_LANG_IN_URL)+strlen($newLang)+2)*(-1));
        $response->setHeader("Location", $location);*/

	}

	public function isVisitedByRobot() {
		$userAgent = $this->getDI()->getRequest()->getHeader('User-agent');
		return preg_match("/(bot|facebook|googlebot|facebookexternalhit|twitterbot|crawler)/i", $userAgent);
	}

	/**
	 * @param string $name
	 * @param null $lang
	 * @return Router\Route
	 */
	public function getRouteByName($name, $lang = null) {
		if ($lang!==null){
			// I need the name of route
			if ($name === 'this') {
				$name = $this->getMatchedRoute()->getName();
			}
			$matches = [];
			preg_match("/(.+)\|[a-z]{2}/", $name, $matches);

			//its lang route
			if(isset($matches[1])){
				return $this->getRouteByName($matches[1]."|".$lang);
			}
		}
		 // is not a lang route
		if ($name === 'this') {
			return $this->getMatchedRoute();
		} else {
			return parent::getRouteByName($name);
		}

	}

	public function mount($group) {
		if (method_exists($group, 'getTranslatedRoutes')) {
			$this->translatedRoutes = array_merge_recursive($this->translatedRoutes, $group->getTranslatedRoutes());
		}

		return parent::mount($group);
	}

	public function getTranslatedRoutes($lang) {
		if (isset($this->translatedRoutes[$lang])) {
			return $this->translatedRoutes[$lang];
		} else {
			return null;
		}
	}

}