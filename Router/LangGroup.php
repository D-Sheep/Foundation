<?php

namespace Foundation\Router;

use Foundation\Logger;
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

		//find keys of routes
		foreach ($this->multilingualRoutes as $route) {
			$tempKeys = [];
			preg_match_all('/<([a-z0-9-]*)>/', $route['pattern'], $tempKeys);
			$keys = array_merge($keys, $tempKeys[1]);

			$routesWithKeys[] = ['route' => $route, 'key' => $tempKeys[1]];
		}

		//translate keys for all routes
		$translations = $langService->getTranslations($keys);

		foreach ($routesWithKeys as $route) {
            $keys = $route['key'];
			foreach ($langService->getAvailableLangs() as $lang) {

                //find translation of pattern parts for lang
				$translationsForRoute = [];
                $key="";
                foreach($keys as $key){
                    if (isset($translations[$lang][$key])) {
                        $translationsForRoute['<' . $key . '>'] = $translations[$lang][$key];
                    } else {
                        $translationsForRoute['<' . $key . '>'] = $key;
                    }
                }

                //translate parts of pattern
                $pattern = strtr($route['route']['pattern'], $translationsForRoute);

                //add route
				$this->add('/{lang:'.$lang.'}/' . $pattern, $route['route']['paths'])->setName($key."|".$lang);;
				$this->translatedRoutes = $translations;
			}
		}
	}

	public function getTranslatedRoutes() {
		return $this->translatedRoutes;
	}
}
