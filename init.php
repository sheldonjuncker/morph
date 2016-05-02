<?php

#Include Porm Files
foreach(glob(__DIR__ . "/porm/*.php") as $file)
{
	include $file;
}

#Include User-created Classes
foreach(glob(__DIR__  . "/classes/*.php") as $file)
{
	include $file;
}

?>