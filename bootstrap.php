<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 24/05/14
 * Time: 12:34
 * To change this template use File | Settings | File Templates.
 */

require 'Exception.php';
require 'Config/ConfigException.php';
require 'Config/Builder.php';
require 'Config/Configurator.php';
require 'Mvc/RenderListener.php';

const CACHE_MAX_LIFETIME = 518400;