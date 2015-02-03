<?php

namespace Foundation\Router;

use Phalcon\DiInterface;

class LangGroup extends \Phalcon\Mvc\Router\Group {

	protected $multilingualRoutes = [];
	protected $translatedRoutes = [];

	public function __construct($paths = null, DiInterface $di = null) {
		parent::__construct([
			'module' => 'Default',
			$di,
		]);
	}

	public function addMultilingualRoute($pattern, $paths) {
		$this->multilingualRoutes[] = ['pattern' => $pattern, 'paths' => $paths];
	}

	protected function generateMultilingualRoutes(DiInterface $di = null) {
		$langService = $di->getLang();

		$keys = [];
		$routesWithKeys = [];

		foreach ($this->multilingualRoutes as $route) {
			$tempKeys = [];
			preg_match_all('/<([a-z0-9-]*)>/', $route['pattern'], $tempKeys);
			$keys = array_merge($keys, $tempKeys[1]);

			$routesWithKeys[] = ['route' => $route, 'keys' => $tempKeys[1]];
		}

		$translations = $langService->getTranslations($keys);

		$currentLangTranslations = [];

		foreach ($routesWithKeys as $route) {
			foreach ($langService->getAvailableLangs() as $lang) {
				$translationsForRoute = [];

				foreach ($route['keys'] as $key) {
					if (isset($translations[$lang][$key])) {
						$translationsForRoute['<' . $key . '>'] = $translations[$lang][$key];
					} else {
						$translationsForRoute['<' . $key . '>'] = $key;
					}
				}
				$pattern = strtr($route['route']['pattern'], $translationsForRoute);

				$this->add('/{lang:'.$lang.'}/' . $pattern, $route['route']['paths']);
				$this->translatedRoutes = $translations;
			}
		}
	}

	public function getTranslatedRoutes() {
		return $this->translatedRoutes;
	}
}
