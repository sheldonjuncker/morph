<?php

/*
	Author:	Sheldon Juncker
	Date:	5/2/2016
	Desc:	
	The MorphConfig class which is part of the
	Morph namespace is used to store the configuration
	information for all of the database, tables, and
	classes that are used by Morph.
	
	It is structured as a list of database names, with
	connection information, namespace information, and
	a list of classes and their associated tables.
	
	Also included are functions for getting the database
	and table names from a MorphClass object.
*/

//The Singleton MorphConfig Class
class MorphConfig
{
	//Default Database
	static $default = "library";
	
	/*
		The folloiwng is an array, with each key being the
		name of a database and the value being itself an 
		array of key-value information about the database.
	*/
	
	static $dbs = [
		//Name of DB
		"library" => [
			"host" => "localhost", //DB Host
			"user" => "root", //DB Username
			"pass" => "", //DB Password
			"driver" => "mysql", //DB Driver Type
		],
	];
	
	/*
		Name:	getFullName
		Args:	MorphClass $object
		Retv:	string $fullTableName ("" on failure)
		Desc:	Returns the full table name for an object.
				In the format of: `dbname`.`tablename`.
	*/
	static function getFullName($object)
	{
		$db = self::getDBShort($object);
		$table = self::getTableShort($object);
		
		if($db == "" || $table == "")
			return "";
		
		else
			return "`$db`.`$table`";
	}
};

?>