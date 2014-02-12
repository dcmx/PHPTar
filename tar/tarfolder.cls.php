<?php
namespace dcm\tar;
use \DirectoryIterator;

class TarFolder extends Tar {

	public function __construct($folder, $name = null) {
		if(!isset($name))
			$name = basename($folder);
		parent::__construct($name);

		$iterator = new DirectoryIterator($folder);

		foreach ($iterator as $path)
			if ($path->isFile())
				$this->add($path->getPathname(), $path->getBasename());
			else if($path->isDir() && !$path->isDot())
				$this->addFolder(new TarFolder($path->getPathname(), $path->getBasename()));
	}
}
