<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 25/06/14
 */

namespace Foundation\Mvc;


class Container {
    /* @var array */
    private $folders;

    /* @var array */
    private $filters;

    public function __construct($f){
        $this->folders = $f;
        $this->filters = [];
    }

    /*
     * Add single folder to folders
     * @param String $name Folders (array of String)
     */
    public function addFolder($name){
        $this->folders[] = $name;
    }

    /*
     * Add array of folders to folders
     * @param array $array Folders (array of String)
     */
    public function addFolders($array){
        $this->folders = $this->folders + $array;
    }

    public function getName(){
        return str_replace(['.', '/'], '', implode("+", $this->folders));
    }

    public function getFolders(){
        return $this->folders;
    }

    public function addFilter($filter){
        $this->filters[] = $filter;
    }

    public function getFilters(){
        return $this->filters;
    }
}