<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lukasjansky
 * Date: 13/01/15
 * Time: 15:49
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Storage;

class LocalStorageDirectory implements IStorageDirectory {
	protected $directoryPath;
	protected $publicPath;

	public function __construct($directoryPath, $publicPath) {
		$this->directoryPath = $directoryPath;
		$this->publicPath = $publicPath;
	}

	public function createFile($fileName, $content) {
		file_put_contents($this->publicPath.'/'.$this->directoryPath.'/'.$fileName, $content);
	}

	public function moveFile($fileName, $file) {
		$file->moveTo($this->publicPath.'/'.$this->directoryPath.'/'.$fileName);
	}

	public function getFilePath($fileName) {
		return $this->directoryPath.'/'.$fileName;
	}

	public function fileExists($fileName) {
		return file_exists($this->publicPath.'/'.$this->directoryPath.'/'.$fileName);
	}
}