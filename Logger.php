<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 21/08/14
 */

namespace Foundation;


use Tracy\Dumper;
use Tracy\Helpers;

class Logger {
    const LOG_PATH = "./../log";

    const DEBUG = 'debug',
        INFO = 'info',
        WARNING = 'warning',
        ERROR = 'error',
        EXCEPTION = 'exception',
        CRITICAL = 'critical';

    public static function log($value, $priority = self::INFO){
        static::isDirectory();

        //$exceptionFile = $value instanceof \Exception ? self::logException($value) : NULL;
       // debug_print_backtrace();
        //var_dump(debug_backtrace());
        $message = self::formatMessage($value);

        $file = self::LOG_PATH . '/log.log'; //. strtolower($priority ?: self::INFO) . '.log';

        if (!@file_put_contents($file, $message . PHP_EOL . PHP_EOL . file_get_contents($file)/*, FILE_APPEND | LOCK_EX*/)) {
            throw new \RuntimeException("Unable to write to log file '$file'. Is directory writable?");
        }

        /*if (in_array($priority, array(self::ERROR, self::EXCEPTION, self::CRITICAL), TRUE) && $this->email && $this->mailer
            && @filemtime(self::LOG_PATH . '/email-sent') + $this->emailSnooze < time() // @ - file may not exist
                && @file_put_contents(self::LOG_PATH . '/email-sent', 'sent') // @ - file may not be writable
        ) {
            call_user_func($this->mailer, $message, implode(', ', (array) $this->email));
        }*/

        //return $exceptionFile;
    }

    public static function debug($value){
        self::log($value,self::DEBUG);
    }

    public static function info($value){
        self::log($value,self::INFO);
    }

    public static function warning($value){
        self::log($value,self::WARNING);
    }

    public static function error($value){
        self::log($value,self::ERROR);
    }

    public static function critical($value){
        self::log($value,self::CRITICAL);
    }

    private static function isDirectory(){
        if (!is_dir(self::LOG_PATH)) {
            throw new \RuntimeException("Directory '".self::LOG_PATH."' is not found or is not directory.");
        }
    }

    /**
     * @return string
     */
    private static function formatMessage($value, $exceptionFile = NULL)
    {
        if ($value instanceof \Exception) {
            while ($value) {
                $tmp[] = ($value instanceof \ErrorException ? 'Fatal error: ' . $value->getMessage() : get_class($value) . ': ' . $value->getMessage())
                    . ' in ' . $value->getFile() . ':' . $value->getLine();
                $value = $value->getPrevious();
            }
            $value = implode($tmp, "\ncaused by ");

        } elseif (!is_string($value)) {
            $value = Dumper::toText($value);
        }

        $value = trim(preg_replace('#\s*\r?\n\s*#', ' ', $value));

        return implode(' ', array(
            @date('[Y-m-d H-i-s]'),
            $value,
            ' @  ' . Helpers::getSource(),
            $exceptionFile ? ' @@  ' . basename($exceptionFile) : NULL
        ));
    }

}