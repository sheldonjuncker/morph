<?php

/*
	Author:	Sheldon Juncker
	Date:	4/3/2016
	Desc:	
	The PormConfig class which is part of the
	Porm namespace is used to store the configuration
	information for all of the database, tables, and
	classes that are used by Porm.
	
	It is structured as a list of database names, with
	connection information, namespace information, and
	a list of classes and their associated tables.
	
	Also included are functions for getting the database
	and table names from a PormClass object.
*/

//The Singleton PormConfig Class
class PormConfig
{
	//Default Database
	static $default = "_cfa";
	
	/*
		The folloiwng is an array, with each key being the
		name of a database and the value being itself an 
		array of key-value information about the database.
	*/
	
	static $dbs = [
		//Name of DB
		"_cfa" => [
			"host" => "localhost", //DB Host
			"user" => "root", //DB Username
			"pass" => "", //DB Password
			"driver" => "mysql", //DB Driver Type
			"namespace" => "", //Namespace for all classes
			"tables" => [
				"Employee" => "teammemberinfo",
				"RequestOff" => "p_request_off",
			] //List of classes => tables
		],
	];
	
	/*
		Name:	getTableShort
		Args:	PormClass $object
		Retv:	string $tableName ("" on failure)
		Desc:	Returns the table name for an object.
				In the format of: tablename.
				On error, returns "";
	*/
	static function getTableShort($object)
	{
		//Loop through all DBs
		foreach(self::$dbs as $db)
			//Loop thorugh all Tables
			foreach($db["tables"] as $class => $table)
				//Test for match
				if(get_class($object) == ($db["namespace"] ? $db["namespace"] . "\\" . $class : $class))
					return $table;
		//Table not found
		return "";
	}
	
	/*
		Name:	getDBShort
		Args:	PormClass $object
		Retv:	string $dbName ("" on failure)
		Desc:	Returns the db name for an object.
				In the format of: dbname.
				On error, returns "";
	*/
	static function getDBShort($object)
	{
		//Loop through all DBs
		foreach(self::$dbs as $dbname => $db)
			//Loop thorugh all Tables
			foreach($db["tables"] as $class => $table)
				//Test for match
				if(get_class($object) == ($db["namespace"] ? $db["namespace"] . "\\" . $class : $class))
					return $dbname;
		//DB not found
		return "";
	}
	
	/*
		Name:	getFullName
		Args:	PormClass $object
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