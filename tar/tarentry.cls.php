<?php
namespace dcm\tar;
use \SplFileInfo;
class HeaderField implements TarTree {
	private $length;
	private $value;
	public function __construct($length, $value) {
		$this->length = $length;
		$this->value = $value;
	}
	public function output() {
		echo $this->value;
	}
	public function getChecksum() {
		$checksum = 0;
		for($i = 0; $i < $this->length; $i++)
			$checksum += (int)ord($this->value[$i]);
		return $checksum;
	}
	public function getSize() {
		return $this->length;
	}
	public function setParent(TarTree $parentFolder) {
	}
	public function getName() {
	}
}

class HeaderFieldString extends HeaderField {
	public function __construct($length, $value) {
		if(strlen($value) > $length)
			throw new TarException('Cannot save string, maximum length '.$length.' exceeded: '.$value);
		$value = str_pad($value, $length, "\x00", STR_PAD_RIGHT);
		parent::__construct($length, $value);
	}
}

class HeaderFieldNumeric extends HeaderField {
	public function __construct($length, $value) {
		$value = decoct($value)."\x00";
		if(strlen($value) > $length)
			throw new TarException('Cannot save numerical value '.$value.', maximum length '.$length.'.');
		$value = str_pad($value, $length, '0', STR_PAD_LEFT);
		parent::__construct($length, $value);
	}
}

class HeaderFieldUnused extends HeaderField {
	public function __construct($length) {
		parent::__construct($length, str_repeat("\x00", $length));
	}
	public function getChecksum() {
		return 0;
	}
}

class TarEntry implements TarTree {

	private $fields = array();
	private $padding;
	private $file;
	private $parentFolder = null;
	private $name;

	private $checksumId;
	private $nameId;
	private $prefixId;

	public function __construct($path, $name = NULL) {

		$this->file = $file = new SplFileInfo($path);

		if(!isset($name))
			$name = $file->getFilename();

		$this->name = $name;

		if(!$file->isFile())
			throw new TarFileNotFoundException($file->getPathname());

		if(!$file->isReadable())
			throw new TarFileNotReadableException($file->getPathname());

		if($file->getSize() > 0x1ffffffff)
			throw new TarFileTooLargeException($file->getPathname());

		$perms = substr(sprintf('%o', $file->getPerms()), -4);

		$i = 0;
		$this->fields[$this->nameId = $i++] = new HeaderFieldUnused(100);				// 100	name			name of file
		$this->fields[$i++] = new HeaderField(8, "000".$perms."\x00");					//   8	mode			file mode
		$this->fields[$i++] = new HeaderFieldNumeric(8, 0);								//   8	uid				owner user ID
		$this->fields[$i++] = new HeaderFieldNumeric(8, 0);								//   8	gid				owner group ID
		$this->fields[$i++] = new HeaderFieldNumeric(12, $file->getSize());				//  12	size			length of file in bytes
		$this->fields[$i++] = new HeaderFieldNumeric(12, $file->getMTime());			//  12	mtime			modify time of file
		$this->fields[$this->checksumId = $i++] = new HeaderField(7, "       ");		//   7	chksum			checksum for header
		$this->fields[$i++] = new HeaderField(1, " ");									//   1	chksum/space	checksum for header
		$this->fields[$i++] = new HeaderField(1, '0');									//   1	typeflag		type of file
		$this->fields[$i++] = new HeaderFieldUnused(100);								// 100	linkname		name of linked file
		$this->fields[$i++] = new HeaderField(6, "ustar\x00");							//   6	magic			USTAR indicator
		$this->fields[$i++] = new HeaderField(2, "00");									//   2	version			USTAR version
		$this->fields[$i++] = new HeaderFieldUnused(32);								//  32	uname			owner user name
		$this->fields[$i++] = new HeaderFieldUnused(32);								//  32	gname			owner group name
		$this->fields[$i++] = new HeaderFieldUnused(8);									//   8	devmajor		device major number
		$this->fields[$i++] = new HeaderFieldUnused(8);									//   8	devminor		device minor number
		$this->fields[$this->prefixId = $i++] = new HeaderFieldUnused(155);				// 155	prefix			prefix for file name
		$this->fields[$i++] = new HeaderFieldUnused(12);								//	12	padding			12 \x00 to pad to 512b	

		$this->setName($name);
		$this->calculateChecksum();

	}

	private function setName($name) {
		$prefix = "";
		if(strlen($name) > 100) {
			$prefix = substr($name, 0, -100);
			$name = substr($name, -100);
			if(strlen($prefix) > 155)
				throw new TarFilenameTooLongException($this->file->getPathname());
		}
		$this->fields[$this->nameId] = new HeaderFieldString(100, $name);
		$this->fields[$this->prefixId] = new HeaderFieldString(155, $prefix);
	}
	private function calculateChecksum() {
		$this->fields[$this->checksumId] = new HeaderField(7, "       ");
		$checksum = 0;
		foreach($this->fields as $field)
			$checksum += $field->getChecksum();
		$this->fields[$this->checksumId] = new HeaderFieldNumeric(7, $checksum);
	}
	

	public function output() {

		$this->setName($this->parentFolder->getName().$this->getName());
		$this->calculateChecksum();

		foreach($this->fields as $field)
			$field->output();
		$this->outputFile();
	}

	private function outputFile() {
		$file = $this->file->openFile();
		$length = $file->fpassthru();
		echo str_repeat("\x00", 512 - ($length & 511));
		flush();
	}

	public function getSize() {
		$length = $this->file->getSize();
		foreach($this->fields as $field)
			$length += $field->getSize();
		$length += 512 - ($length & 511);
		return $length;
	}

	public function getName() {
		return $this->name;
	}

	public function setParent(TarTree $parentFolder) {
		$this->parentFolder = $parentFolder;
	}

}

