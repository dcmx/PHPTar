<?php
namespace dcm\tar;
use \Exception;
class TarException extends Exception {
	public function __construct($message) {
		parent::__construct('Exception in PHPTar: '.$message);
	}
}

class TarUnableToAddException extends TarException {
	public function __construct($name, $message = null) {
		parent::__construct('Could not add file '.$name.( isset($message) ? ': '.$message : '.'));
	}
}

class TarFileNotReadableException extends TarUnableToAddException {
	public function __construct($name) {
		parent::__construct($name, 'File is not readable!');
	}
}

class TarFileNotFoundException extends TarUnableToAddException {
	public function __construct($name) {
		parent::__construct($name, 'File not found!');
	}
}

class TarFileTooLargeException extends TarUnableToAddException {
	public function __construct($name, $size = -1) {
		parent::__construct($name, 'Filesize'.(($size >= 0) ? ' of '.(round($size/10737418.24)/100).' GiB' : '').' exceeds limit of 8 GiB.');
	}
}

class TarFilenameTooLongException extends TarUnableToAddException {
	public function __construct($name) {
		parent::__construct($name, 'Filename exceeds limit of 255 characters!');
	}
}
