<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lukasjansky
 * Date: 13/01/15
 * Time: 15:49
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Storage;

use Aws\S3\S3Client;

class AmazonS3StorageService implements IStorageService {
	protected $directories = [];
	protected $client;
	protected $bucket;

	public function __construct($bucket) {
		$this->client = S3Client::factory(array(
		));
		$this->bucket = $bucket;
	}

	public function createFile($fileName, $directory, $content) {
		$result = $this->client->putObject(array(
		    'Bucket'     => $this->bucket,
		    'Key'        => $directory.'/'.$fileName,
		    'Body' => $content,
		    'ACL' => 'public-read'
		));
	}

	public function moveFile($fileName, $directory, $file) {
		$result = $this->client->putObject(array(
		    'Bucket'     => $this->bucket,
		    'Key'        => $directory.'/'.$fileName,
		    'SourceFile' => $file->getPath().'/'.$file->getFilename(),
		    'ACL' => 'public-read'
		));
	}

	public function getFilePath($fileName, $directory) {
		$url = $this->client->getObjectUrl($this->bucket,  $directory.'/'.$fileName);
		return $url;
	}

	public function addDirectory($name, $path) {
		$this->directories[$name] = $path;
	}

	public function fileExists($fileName, $directory) {
		return $this->client->doesObjectExist($this->bucket, $directory.'/'.$fileName);
	}
}