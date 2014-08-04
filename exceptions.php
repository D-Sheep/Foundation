<?php
namespace Foundation;

/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 04/08/14
 */

/**
 * Bad HTTP / presenter request exception.
 */
class BadRequestException extends \Foundation\Exception
{
    /** @var int */
    protected $defaultCode = 404;


    public function __construct($message = '', $code = 0, \Exception $previous = NULL)
    {
        if ($code < 200 || $code > 504) {
            $code = $this->defaultCode;
        }

        {
            parent::__construct($message, $code, $previous);
        }
    }

}