<?php
namespace dcm\tar;

class Tar implements TarTree {

	private $name;
	private $files = array();
	private $folders = array();
	private $parentFolder = null;

	public function __construct($name = "archive.tar") {
		$this->name = $name;
	}

	public function addFolder(TarTree $tar) {
		$this->folders[] = $tar;
		$tar->setParent($this);
	}
	public function setParent(TarTree $parentFolder) {
		$this->parentFolder = $parentFolder;
	}

	public function getName() {
		if(!isset($this->parentFolder))
			return '';
		return $this->parentFolder->getName().$this->name.'/';
	}
	
	public function add($path, $name = null) {
		if(isset($name))
			$name = basename($name);
		else
			$name = basename($path);
		
		$this->files[] = $entry = new TarEntry($path, $name);
		$entry->setParent($this);
	}

	public function getSize() {
		$size = 0;
		foreach($this->files as $file)
			$size+= $file->getSize();
		foreach($this->folders as $folder)
			$size += $folder->getSize();

		return $size;
	}

	public function header() {
		header('content-type: application/x-tar');
		header('content-disposition: attachment;filename="'.$this->name.'"');
		header('content-length: '.$this->getSize());
	}

	public function output() {
		foreach($this->files as $file)
			$file->output();
		foreach($this->folders as $folder)
			$folder->output();
	}

}
