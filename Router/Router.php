<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Router
 *
 * @author kamilhurajt
 */
namespace Foundation\Router;

class Router extends \Phalcon\Mvc\Router {

    /**
     * @return string
     */
    public static function detectBasePath() {

        // path & query
        if (isset($_SERVER['REQUEST_URI'])) { // Apache, IIS 6.0
            $requestUrl = $_SERVER['REQUEST_URI'];

        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0 (PHP as CGI ?)
            $requestUrl = $_SERVER['ORIG_PATH_INFO'];
            if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') {
                $requestUrl .= '?' . $_SERVER['QUERY_STRING'];
            }
        } else {
            $requestUrl = '';
        }

        $tmp = explode('?', $requestUrl, 2);

        $urlPath = preg_replace('#/{2,}#', '/', $tmp[0]);

        // detect script path
        if (isset($_SERVER['SCRIPT_NAME'])) {
            $script = $_SERVER['SCRIPT_NAME'];
        } elseif (isset($_SERVER['DOCUMENT_ROOT'], $_SERVER['SCRIPT_FILENAME'])
            && strncmp($_SERVER['DOCUMENT_ROOT'], $_SERVER['SCRIPT_FILENAME'], strlen($_SERVER['DOCUMENT_ROOT'])) === 0
        ) {
            $script = '/' . ltrim(strtr(substr($_SERVER['SCRIPT_FILENAME'], strlen($_SERVER['DOCUMENT_ROOT'])), '\\', '/'), '/');
        } else {
            $script = '/';
        }

        $scriptPath = '/';

        $path = strtolower($urlPath) . '/';
        $script = strtolower($script) . '/';
        $max = min(strlen($path), strlen($script));
        for ($i = 0; $i < $max; $i++) {
            if ($path[$i] !== $script[$i]) {
                break;
            } elseif ($path[$i] === '/') {
                $scriptPath = substr($urlPath, 0, $i + 1);
            }
        }

        return $scriptPath;
    }

}
