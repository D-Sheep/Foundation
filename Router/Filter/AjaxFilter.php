<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 30/07/14
 * Time: 10:04
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Router\Filter;


class AjaxFilter {

    public function check()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

}