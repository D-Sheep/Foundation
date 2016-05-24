<?php
/**
 * Created by PhpStorm.
 * User: jurajhrib
 * Date: 20.9.2014
 * Time: 14:12
 */

namespace Foundation\Localization;

interface ILocatorService {

    public function getUserLanguage();

    public function getUserPlaceLocality();

    public function getUserCountry();

    public function getDefaultLanguage();

}