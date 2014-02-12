<?php

require 'tar/tar.inc.php';

use dcm\tar\Tar;
use dcm\tar\TarFolder;

// create container
$tar = new Tar("TarArchive.tar");

// (optional) create subfolder
$subfolder = new Tar("ExampleFiles");

// add some files
$subfolder->add('/etc/mime.types', 'mime.types');
$subfolder->add('/etc/hosts', 'hosts');
// the second argument can be omitted
// the filename in the tar will be the original file's name
$subfolder->add('/etc/hostname');

// add subfolder to archive
$tar->addFolder($subfolder);

// entire folders can be added as well, preserving the structure
$folder = new TarFolder('/var/log/apt');
$tar->addFolder($folder);

// output HTTP headers (useful as a download for a website)
$tar->header();

// print tar to stdout
$tar->output();

?>
