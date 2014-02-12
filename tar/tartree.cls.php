<?php
namespace dcm\tar;

interface TarTree {
	public function getSize();
	public function output();
	public function setParent(TarTree $parent);
	public function getName();
}

