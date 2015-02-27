<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lukasjansky
 * Date: 13/01/15
 * Time: 15:46
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Storage;

interface IStorageDirectory {

	public function createFile($fileName, $content);

	public function moveFile($fileName, $file);

	public function getFilePath($fileName);

	public function fileExists($fileName);
}