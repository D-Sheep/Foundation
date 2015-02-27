<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lukasjansky
 * Date: 13/01/15
 * Time: 15:49
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Storage;

class AmazonS3StorageDirectory implements IStorageDirectory {
	protected $client;
	protected $bucket;
	protected $directory;

	public function __construct($client, $bucket, $directory) {
		$this->client = $client;
		$this->bucket = $bucket;
		$this->directory = $directory;
	}

	public function createFile($fileName, $content) {
		$result = $this->client->putObject(array(
		    'Bucket'     => $this->bucket,
		    'Key'        => $this->directory.'/'.$fileName,
		    'Body' => $content,
		    'ACL' => 'public-read'
		));
	}

	public function moveFile($fileName, $file) {
		$result = $this->client->putObject(array(
		    'Bucket'     => $this->bucket,
		    'Key'        => $this->directory.'/'.$fileName,
		    'SourceFile' => $file->getPath().'/'.$file->getFilename(),
		    'ACL' => 'public-read'
		));
	}

	public function getFilePath($fileName) {
		$url = $this->client->getObjectUrl($this->bucket,  $this->directory.'/'.$fileName);
		return $url;
	}

	public function fileExists($fileName) {
		return $this->client->doesObjectExist($this->bucket, $this->directory.'/'.$fileName);
	}
}