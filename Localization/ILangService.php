<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 21/08/14
 * Time: 16:20
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Localization;


interface ILangService {

    public function getUserDefaultLanguage();

    public function isMatchingUserDefaultLanguage($lang);

    public function translate($variable, $lang = null);

}