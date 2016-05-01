<?php

/*
	Author:	Sheldon Juncker
	Date: 4/3/2016
	Desc:	
	The Porm class is the main class used by Porm
	to connect to the databases.
	
	This class is primarily used as a singleton,
	but
	it does have several uses when instantiated.
	
	When instantiated:
	
	The constructor acceptsa database name
	which it uses for db reads.
	The only public function is for reading a result
	set from the database.
	
	When used as a Singleton:
	
	It stores an array for all database connections
	indexed by the database's name. Only when a
	connection is needed is the connection created
	and added to the array.
	
	The singleton also stores the last connection
	used which can be retrieved and is useful when
	only one database is being used.
	
	There are a variety of functions for working with
	the db connections that Porm manages. There
	is one for adding a connection to Porm, one for
	getting a connection form Porm, and one for
	getting a connection not managed by Porm.
*/

class Porm
{
	#Public Properties
	
	//PDO Connection
	public $con;
	
	
	#Static Properties
	
	//List of DB Connections
	static $dbs = [];
	
	//Last Used DB Connection
	static $last = NULL;
	
	
	#Public Methods
	
	
	/*
		Name:	__construct
		Args:	string $dbname = false
		Retv:	Porm $object
		Desc:	The constructor accepts a database
		name and returns a Porm object with that
		db connection. If no dbname is given, it
		uses the default db specified in PormConfig.
	*/
	public function __construct($dbname = "")
	{
		//Get default database if not given
		$dbname = $dbname != "" ? $dbname : PormConfig::$default;
		
		//Set connection
		$this->con = self::getPDO($dbname);
	}
	
	/*
		Name:	query
		Args:	string $sql, array $params = []
		Retv:	Returns a result set array of
		StdClass objects.
		Desc:	This method is used for performing
		generic db reads from a sql string and an
		optional array of paremeters to bind.
	*/
	public function query($sql, $params = [])
	{
		return self::readAll($this->con, $sql, $params);
	}
	
	
	#Static Methods
	
	/*
		Name:	getCon
		Args:	PormClass $object | string $dbname
		Retv:	Returns the PDO object or NULL on
		failure.
		Desc:	Accepts the name of the db or a
		PormClass object from which the db name
		is retreived. If the db connection exists,
		it is returned. If it doesn't exist, it is
		added and returned. If the $dbname is not
		passed, it returns the last used
		database connection.
	*/
	static function getCon($name = NULL)
	{
		//If $name is object, use object's dbname
		if(is_object($name))
		{
			$name = PormConfig::getDBShort($name);
		}
		
		//Get Last Used
		if($name == NULL)
			return self::$last;
		//Get Existing
		else if(isset(self::$dbs[$name]))
			return self::$dbs[$name];
		//Create and Get
		else
			return self::addCon($name);
	}
	
	/*
		Name:	addCon
		Args:	string $dbname
		Retv:	The created connection or NULL
		on failure.
		Desc:	Adds the database connection to
		be managed by Porm.
	*/
	static function addCon($name)
	{
		//If database exists
		if(isset(PormConfig::$dbs[$name]))
		{
			return self::$dbs[$name] = self::getPDO($name);
		}
		
		//Database name not found
		else
		{
			return NULL;
		}
	}
	
	/*
		Name:	getPDO
		Args:	string $dbname
		Retv:	A PDO connection or NULL on failure
		Desc:	Gets a PDO connection from the name
		of a database. This connection is not 
		managed by Porm but can be used at the 
		user's discretion. 
	*/
	static function getPDO($name = "")
	{
		//If $name is object, use object's dbname
		if(is_object($name))
		{
			$name = PormConfig::getDBShort($name);
		}
		
		//Last Used
		if($name == "")
		{
			return self::$last;
		}
		
		//Last Used
		if($name == "")
		{
			return $last;
		}
		
		//If database exists
		if(isset(PormConfig::$dbs[$name]))
		{
			$db = PormConfig::$dbs[$name];
			$con = new \PDO("{$db['driver']}:dbname={$name};host={$db['host']}", $db['user'], $db['pass']);
			return $con;
		}
		
		//Database not found
		else
		{
			return NULL;
		}
	}
	
	/*
		Name:	readAll
		Args:	
			PDO $con,
			string $sql,
			array $params
			object $type = 'StdClass'
		Retv:	An array of PormClass objects
		or an empty array on failure
		Desc:	This method is used by the PormClass
		objects to read an array of results from
		the database. Not intended to be used by
		the user directly.
	*/
	static function readAll($con, $sql, $params = [], $type = 'StdClass')
	{
		//Prepare Query
		$stmt = self::createStatement($con, $sql, $params);
		
		$stmt->setFetchMode(\PDO::FETCH_CLASS, $type);
		
		//Execute Statement
		$stmt->execute();
		
		$objects = $stmt->fetchAll();
		
		$stmt->closeCursor();
		
		//Return [] on failure (PDO->fetch returns false)
		if($objects === false)
			$objects = [];
		
		return $objects;
	}
	
	/*
		Name:	read
		Args:	
			PDO $con,
			string $sql,
			array $params
			object $type = 'StdClass'
		Retv:	A PormClass object or NULL on failure
		Desc:	This method is used by the PormClass
		objects to read a single result from
		the database. Not intended to be used by
		the user directly.
	*/
	static function read($con, $sql, $params = [], $type = 'StdClass')
	{
		//Prepare Query
		$stmt = self::createStatement($con, $sql, $params);
		
		$stmt->setFetchMode(\PDO::FETCH_CLASS, $type);
		
		//Execute Statement
		$stmt->execute();

		$object = $stmt->fetch();
		
		$stmt->closeCursor();
		
		//Return NULL on failure (PDO->fetch returns false)
		if($object === false)
			$object = NULL;
		
		return $object;
	}
	
	/*
		Name:	createStatement
		Args:	PDO $con, string $sql, array $vals = []
		Retv:	A PDO statement with bound values
		Desc:	From a PDO connection, a SQL string,
		and an array of values to bind, this
		method returns a PDO statement.
	*/
	static function createStatement($con, $sql, $vals = [])
	{
		//Last Used
		self::$last = $con;
		
		//Prepare Statement
		$stmt = $con->prepare($sql);
		
		//Bind Values
		foreach($vals as $key => $val)
		{
			$stmt->bindValue($key+1, $val);
		}
		
		return $stmt;
	}
	
	/*
		Name:	executeStatement
		Args:	PDO $con, string $sql, array $vals = []
		Retv:	The result from the executed statement
		Desc:	The method creates the PDO statement, 
		executes it, and closes the cursor before 
		returning the results which can be fetched.
	*/
	static function executeStatement($con, $sql, $vals = [])
	{
		//Set Last Used
		self::$last = $con;
		$stmt = self::createStatement($con, $sql, $vals);
		$result = $stmt->execute();
		$stmt->closeCursor();
		return $result;
	}
}

?>