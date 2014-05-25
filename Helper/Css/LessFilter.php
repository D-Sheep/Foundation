<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 25/05/14
 * Time: 09:55
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Helper\Css;


use Phalcon\Assets\FilterInterface;
use Phalcon\Crypt\Exception;

class LessFilter implements FilterInterface {

    /**
     * Filters the content returning a string with the filtered content
     *
     * @param string $content
     * @return $content
     */
    public function filter($content)
    {
        $les = new Lessc();
        $les->addImportDir(WWW_DIR . '/bootstrap');
        return $les->compile($content);
    }


}