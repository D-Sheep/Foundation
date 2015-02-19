<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lukasjansky
 * Date: 13/01/15
 * Time: 15:49
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Storage;

class LocalStorageService implements IStorageService {
	protected $directories = [];
	protected $uploadDirectory;
	protected $publicPath;

	public function __construct($publicPath, $uploadDirectory) {
		$this->publicPath = $publicPath;
		$this->uploadDirectory = $uploadDirectory;
	}

	public function createFile($fileName, $directory, $file) {
		$file->moveTo($this->publicPath.'/'.$this->uploadDirectory.'/'.$this->directories[$directory].'/'.$fileName);
	}

	public function getFilePath($fileName, $directory) {
		return $this->uploadDirectory.'/'.$this->directories[$directory].'/'.$fileName;
	}

	public function addDirectory($name, $path) {
		$this->directories[$name] = $path;
	}
}