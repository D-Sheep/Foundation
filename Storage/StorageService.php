<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lukasjansky
 * Date: 13/01/15
 * Time: 15:49
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Storage;

class StorageService {
	protected $directories = [];

	public function setDirectory($name, $directory) {
		$this->directories[$name] = $directory;
	}

	public function getDirectory($name) {
		if (isset($this->directories[$name])) {
			return $this->directories[$name];
		} else {
			return null;
		}
	}
}