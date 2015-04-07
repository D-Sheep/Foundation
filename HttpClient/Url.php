<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\HttpClient;
/**
 * Description of Url
 *
 * @author David Menger
 */
class Url extends \Nette\Http\Url {

    protected $queryArray = array();

    public function __construct($url = NULL) {
        parent::__construct($url);
        \parse_str($this->getQuery(), $this->queryArray);
    }

    public function setQuery($value) {
        parent::setQuery($value);
        $this->queryArray = array();
        \parse_str($value, $this->queryArray);
    }

    public function appendQuery($value) {
        parent::appendQuery($value);
        $this->queryArray = array();
        \parse_str($this->getQuery(), $this->queryArray);
    }

    public function setParam($key, $value) {
        $this->queryArray[$key] = $value;
        parent::setQuery(\http_build_query($this->queryArray));
    }

    public function getParam($key) {
        return isset($this->queryArray[$key]) ? $this->queryArray[$key] : null;
    }

    public function unsetParam($key) {
        if (isset($this->queryArray[$key])) unset($this->queryArray[$key]);
    }

    public function getParams() {
        return $this->queryArray;
    }

}