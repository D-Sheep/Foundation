<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 05/08/14
 * Time: 11:40
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Utils;


use Foundation\Exception;
use Nette\Utils\Strings;

class Filter extends Strings {

    public static function sanitizeUrlInput($url) {
        return self::webalize($url);
    }

    /**
     *
     * @param type $phone
     * @return type
     */
    public static function sanitizePhone($phone) {
        $num = preg_replace("/[^0-9]/", "", $phone->getValue());
        return intval(substr($num, strlen($num)-9, 9));
    }

    public static function encodeNumber($number, $short = false, $longNumber = 73) {
        if (strlen($number) > ($short ? 11 : 16) || $number < 1) {
            throw new Exception("Potencial overflow");
        }
        $mod = $number%36;
        $res = base_convert(($number+($short ? 73 : 9876543)) * ($short ? 3 : ($longNumber-$mod)), 10, $short ? 36 : 30);
        while (strlen($res)%3 != 0) {
            $res = "0".$res;
        }
        if ($short) {
            return base64_encode($res);
        } else {
            return base_convert($mod, 10, 36) . base64_encode($res);
        }
    }

    public static function decodeNumber($number, $short = false, $longNumber = 73) {
        if (!$short) {
            $mod = base_convert(substr($number, 0, 1), 36, 10);
            $number = substr($number, 1);
        }
        $n = (base_convert(base64_decode($number), $short ? 36 : 30, 10) / ($short ? 3 : ($longNumber-$mod))) - ($short ? 73 : 9876543);
        if (round($n) != $n) {
            throw new Exception('Neplatný kód');
        }
        return $n;
    }

    public static $chars = 'dfFl6zYqj7kMcmCeg5hEwDHQstWBLI8xG314XPbyarNUv2J9uRTZnO0pKoSVAi';

    public static function shorten($num, $minLength, $chars = null, $affine = 0) {
        if (!$num) return null;
        $chars = str_split($chars ? $chars : self::$chars);
        $base  = $divider = count($chars);
        if ($minLength > 1) {
            $num = bcadd($num, bcsub(bcpow($base, $minLength-1), 1));
        }
        $pos = 0;
        $add = $affine;
        $ret = array();
        while ($num > 0) {
            $r   = bcmod($num, $base);
            $num = bcdiv($num, $base);
            if (strpos($num, ".")) {
                $num = substr($num, 0, strpos($num, "."));
            }
            if ($affine) {
                $r = ($r+$add+$pos)%$base;
                $add = $r;
            }
            $ret[$pos] = $chars[$r];
            $pos++;
        }
        return implode("", array_reverse($ret));
    }

    public static function deshorten($num, $minLength, $chars = null, $affine = 0) {
        if (!$num) return null;
        $num = self::toAscii($num);
        $chars = $chars ? $chars : self::$chars;
        $num = preg_replace("[^".$chars."]", "", $num);
        $chars = array_flip(str_split($chars));
        $base  = count($chars);
        $numArray = array_reverse(str_split($num));
        $ret = 0;
        $sub = $affine;
        foreach ($numArray as $pos => $char) {
            $val = $chars[$char];
            if ($affine) {
                $min = $val-$sub-$pos;
                $sub = $val;
                $val = ($min < 0 ? $base + $min : $min);
            }
            $k = $pos > 0 ? bcpow($base, $pos) : 1;
            $ret = bcadd($ret, bcmul($k ? $k : "0", $val));
        }
        if ($minLength > 1) {
            $ret = bcsub($ret, bcsub(bcpow($base, $minLength-1), 1));
        }
        return \abs($ret);
    }

    public static function stringDate(\DateTime $inputDate, $now = null) {
        $diff = $inputDate->diff($now !== null ? $now : new \DateTime());
        /* @var $diff \DateInterval  */

        if ($diff->y > 0) {
            return sprintf('%dy', $diff->y);
        }

        if ($diff->days > 0) {
            return sprintf('%dd', round($diff->days));
        }

        if ($diff->h > 0) {
            return sprintf('%dh', round($diff->h));
        }

        if ($diff->i > 0) {
            return sprintf('%dm', round($diff->i));
        }


        return sprintf('%ds', round($diff->s));
    }

}