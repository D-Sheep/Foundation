<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\HttpClient;

use Foundation\HttpClient\Response;
/**
 * Description of Request
 *
 * @author David Menger
 */
class Request extends \Foundation\HttpClient\GeneralMessage  {
    private $saveToFile;
    private $dontFollowRedirect = false;
    private $headersFromReq;
    /**
     * @param $fileName
     * @return $this
     */
    public function saveResponseToFile($fileName)
    {
        $this->saveToFile = $fileName;
        return $this;
    }
    /**
     * @return $this
     */
    public function dontFollowRedirect()
    {
        $this->dontFollowRedirect = true;
        return $this;
    }

    private function parseHeader($ch, $header) {
        $this->headersFromReq = $this->headersFromReq === null ? $header : $this->headersFromReq . $header;
        return strlen($header);
    }

    public function run($expectedResponseType = self::CONTENT_TYPE_QUERY) {

        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $this->url->getAbsoluteUrl());

        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        if ($this->method == "POST") {
            curl_setopt($ch,CURLOPT_POST,  1);
            $rd = $this->getRawData();
            curl_setopt($ch,CURLOPT_POSTFIELDS, $rd);
        } else if ($this->method == "PUT") {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            if (\count($this->postData)>0) {
                curl_setopt($ch,CURLOPT_POSTFIELDS, $this->getRawData());
            }
        } else if ($this->method != "GET") {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
        }

        curl_setopt($ch,CURLOPT_HTTPHEADER, $this->getRequestParsedHeaders());
        if ($this->dontFollowRedirect === false) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }
        if ($this->saveToFile !== null) {
            $fp = fopen($this->saveToFile, 'w+');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, [$this, 'parseHeader']);
        } else {
            curl_setopt($ch,CURLOPT_HEADER, true);
        }
        //execute post

        $response = curl_exec($ch);

        if ($this->saveToFile === null) {

            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
            // IS JSON?

            if ($expectedResponseType == self::CONTENT_TYPE_JSON) {
                $postData = \Nette\Utils\Json::decode($body);

            } else if ($expectedResponseType == self::CONTENT_TYPE_QUERY) {
                $postData = null;
                \parse_str($body, $postData);
            } else {
                $postData = null;
            }
        } else {
            $header = $this->headersFromReq;
            $postData = null;
            $body = null;
        }

        //close connection
        curl_close($ch);
        if ($this->saveToFile !== null) {
            fclose($fp);
        }

        # Extract the version and status from the first header

        $headers = $this->parseHeaders($header);
        $matches = null;
        preg_match('#^HTTP/(\d\.\d)\s(\d\d\d)\s(.*)\n#', $header, $matches);
        if (empty($matches[2])) {
            $statusCode = 500;
        } else {
            $statusCode = $matches[2];
        }



        $ret =  new Response($this->url, $this->method, $postData, $headers, $expectedResponseType, $statusCode, $body);

        return $ret;
    }

    protected function isJson($body) {
        static $pcre_regex = '
            /
            (?(DEFINE)
               (?<number>   -? (?= [1-9]|0(?!\d) ) \d+ (\.\d+)? ([eE] [+-]? \d+)? )    
               (?<boolean>   true | false | null )
               (?<string>    " ([^"\\\\]* | \\\\ ["\\\\bfnrt\/] | \\\\ u [0-9a-f]{4} )* " )
               (?<array>     \[  (?:  (?&json)  (?: , (?&json)  )*  )?  \s* \] )
               (?<pair>      \s* (?&string) \s* : (?&json)  )
               (?<object>    \{  (?:  (?&pair)  (?: , (?&pair)  )*  )?  \s* \} )
               (?<json>   \s* (?: (?&number) | (?&boolean) | (?&string) | (?&array) | (?&object) ) \s* )
            )
            \A (?&json) \Z
            /six   
          ';
        return \preg_match($pcre_regex, $body);
    }

    protected function parseHeaders($header) {
        $retVal = array();
        $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
        foreach( $fields as $field ) {
            $match = null;
            if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
                $match[1] = \preg_replace_callback('/(?<=^|[\x09\x20\x2D])./', function($i){return \strtoupper($i[0]); }, strtolower(trim($match[1])));
                //$match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
                if( isset($retVal[$match[1]]) ) {
                    $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
                } else {
                    $retVal[$match[1]] = trim($match[2]);
                }
            }
        }
        return $retVal;
    }

    protected function getRequestParsedHeaders() {
        $ret = array();
        foreach ($this->headers as $key => $value) {
            $ret[] = "{$key}: $value";
        }
        return $ret;
    }

}