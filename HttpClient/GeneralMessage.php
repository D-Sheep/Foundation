<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 24.3.15
 * Time: 9:58
 */

namespace Foundation\HttpClient;

use Foundation\HttpClient\Url;


abstract class GeneralMessage {
    const CONTENT_TYPE_JSON = 1;
    const CONTENT_TYPE_QUERY = 2;
    const CONTENT_TYPE_RAW = 3;
    const CONTENT_TYPE_ENCRYPTED = 4;

    /**
     *
     * @var \Foundation\Oauth\Client\Url
     */
    protected $url;

    protected $method;

    protected $headers = array();

    protected $postData;

    protected $hardRawData;

    public function __construct(Url $url, $method = "GET", $postData = array(), $headers = array(), $contentType = self::CONTENT_TYPE_QUERY) {
        $this->url = $url;
        $this->method = \strtoupper($method);
        $this->postData = (array) $postData;
        $this->headers = $headers;
        $this->contentType = $contentType;
    }

    protected function setHardRawData($data) {
        $this->hardRawData = $data;
    }

    public function getData() {
        return $this->postData;
    }

    public function getRawData() {
        if ($this->hardRawData !==null) {
            return $this->hardRawData;
        } else {
            return $this->dataDecodeToType($this->postData, $this->contentType);
        }
    }

    protected function dataDecodeToType($data, $contentType) {
        if ($contentType == self::CONTENT_TYPE_JSON) {
            return \Nette\Utils\Json::encode((object) $data);
        } else if ($contentType == self::CONTENT_TYPE_QUERY) {
            return http_build_query($data, null, "&", PHP_QUERY_RFC3986);
        }
    }
}