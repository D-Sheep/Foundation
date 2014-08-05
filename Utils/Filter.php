<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 05/08/14
 * Time: 11:40
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Utils;


use Nette\Utils\Strings;

class Filter extends Strings {

    public static function sanitizeUrlInput($url) {
        return self::webalize($url);
    }

}