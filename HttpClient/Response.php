<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\HttpClient;

/**
 * Description of Response
 *
 * @author David Menger
 */
class Response extends \Foundation\HttpClient\GeneralMessage {

    protected $code;

    protected $rawData;
    public function __construct(Url $url, $method = "GET", $postData = array(), $headers = array(), $contentType = self::CONTENT_TYPE_QUERY, $statusCode = 200, $rawData = null) {
        $this->code = $statusCode;
        $this->rawData = $rawData;
        return parent::__construct($url, $method, $postData, $headers, $contentType);
    }

    public function getRawData() {
        if ($this->rawData==null) {
            $this->rawData = parent::getRawData();
        }
        return $this->rawData;
    }

    public function isOk($verifyJsonOkSign = false) {
        return in_array($this->code, array(200, 201)) && (!$verifyJsonOkSign || !empty($this->postData["ok"]));
    }

    public function isContinue() {
        return ($this->code == 100);
    }

}
