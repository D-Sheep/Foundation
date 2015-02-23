<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lukasjansky
 * Date: 13/01/15
 * Time: 15:46
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Storage;

interface IStorageService {

	public function createFile($fileName, $directory, $content);

	public function moveFile($fileName, $directory, $file);

	public function getFilePath($fileName, $directory);

	public function fileExists($fileName, $directory);

	public function addDirectory($name, $path);
}