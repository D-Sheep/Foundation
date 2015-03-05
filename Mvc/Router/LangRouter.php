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

	public function beforeExecuteRoute(Event $event, Dispatcher $dispatcher) {

		$lang = $dispatcher->getParam(self::LANG_PARAM);
		$route = $dispatcher->getDI()->getRouter()->getMatchedRoute();

		if ($route !== null) {
			$paths = $route->getPaths();
			$isLanguageRoute = array_key_exists(self::LANG_PARAM, $paths);
		} else {
			$isLanguageRoute = false;
		}

		//$dispatcher->getActiveController() má interface mam hromadu jazyku (baseconstoler - metalang)
		// jinak použiju this

		$request = $dispatcher->getDI()->getRequest();
		$queryParams = $request->getQuery();
		if (isset($queryParams[self::SET_LANG_IN_URL]) && !$this->lang->isMatchingUserDefaultLanguage($queryParams[self::SET_LANG_IN_URL])){
			$newLang =$queryParams[self::SET_LANG_IN_URL];
			$this->redirectToLang($dispatcher, $newLang, true);
		}


		if ($isLanguageRoute
			 && !$this->isVisitedByRobot()
			 && !$this->lang->isMatchingUserDefaultLanguage($lang)) {

			$langParam = $this->lang->getUserDefaultLanguage();

			$this->redirectToLang($dispatcher, $langParam);
		}
	}

	private function redirectToLang(Dispatcher $dispatcher, $newLang, $setLangInSession = false){
		//TODO je lang podporovaný? LangService->getAvailableLangs
		//co dělat když neni podporovanej?
		$controller = $dispatcher->getActiveController();
		if (method_exists($controller, "getAlternativeLinkForLang")){
			$arrayForLink = $controller->getAlternativeLinkForLang($newLang);
			$response = $this->getDI()->getResponse()->redirect($arrayForLink);
		} else {
			$dispatcher->setParam('lang', $newLang);
			$response = $this->getDI()->getResponse()->redirect([
				self::LANG_PARAM => $newLang,
				'for' => 'this'
			]);
		}
		if ($setLangInSession) {
			$session = $dispatcher->getDI()->getSession();
			$session->set(LangService::STORED_SESSION_LANG, $newLang);
		}
		/*$headers = $response->getHeaders();
        $location = $headers->get("Location");
        $location = substr($location, 0, (strlen(self::SET_LANG_IN_URL)+strlen($newLang)+2)*(-1));
        $response->setHeader("Location", $location);*/
		$response->send();
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