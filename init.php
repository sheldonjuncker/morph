<?php

#Include Morph Files
foreach(glob(__DIR__ . "/morph/*.php") as $file)
{
	include $file;
}

#Include User-created Classes
foreach(glob(__DIR__  . "/classes/*.php") as $file)
{
	include $file;
}

?>