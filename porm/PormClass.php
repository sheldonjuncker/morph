<?php

/*
	Author:	Sheldon Juncker
	Date:	4/3/2016
	Desc:
	The class PormClass is the class that
	every table object inherits from.
	It contains all of the required methods
	for creating, reading, updating, 
	and deleting (CRUD).
	
	It can also perform joined queries
*/

class PormClass
{
	//Array of Fields for DB
	public $fields;
	
	#Non-static Public Methods
	
	#CRUD Methods
	
	/*
		Name:	create
		Args:	bool $sync = true
		Retv:	The result of a PDO statement
		execution (boolean for success/failure)
		Desc:	This method creates (INSERTs) a
		row into the database from the PormClass
		object. If $sync is true, it auto updates
		the object with the values inserted that
		wouldn't be available to PHP. (Such as
		timestamps, and other fields with default
		values.)
	*/
	public function create($sync = true)
	{
		//Get Database Connection
		$con = Porm::getCon($this);
		
		//Get Table Name
		$table = PormConfig::getFullName($this);
		
		//SQL
		$sql = "INSERT INTO $table(";
			
		//Properties, Values, ?'s (for preparing)
		$props = [];
		$vals = [];
		$qmarks = [];
			
		foreach($this->fields as $field)
		{
			$val = $this->{$field};
			
			//Skip false values (Booleans are not a valid MySQL data type)
			if($val === false)
				continue;
			
			$vals[] = $val;
			$props[] = "`" . $field . "`";
			$qmarks[] = '?';
		}
			
		//Create SQL Command
		$sql .= implode(",", $props) . ") VALUES(" . implode(",", $qmarks) . ")";
		
		//Statement
		$result = Porm::executeStatement($con, $sql, $vals);
		
		//Sync Object with Created One
		if($sync && $result)
		{
			//ID of Object
			$id = $con->lastInsertId();
			
			//Get Created
			$created = $class::read((int) $id);
			
			//Sync
			foreach($created->fields as $field)
			{
				$this->{$field} = $created->{$field};
			}
		}
		
		return $result;
	}
	
	/*
		Name:	update
		Args:	void
		Retv:	The result of a PDO statement
		execution (boolean for success/failure)
		Desc:	This method updates a database
		row from the PormClass object, which
		has presumably been changed.
	*/
	public function update()
	{
		//Get Database Connection
		$con = Porm::getCon($this);
		
		//Get Table Name
		$table = PormConfig::getFullName($this);
		
		//SQL
		$sql = "UPDATE $table SET";
		
		//Properties and Values
		$props = [];
		$vals = [];
		
		//Get Values to Update
		foreach($this->fields as $field)
		{
			//Value
			$val = $this->{$field};
			
			//Don't update false values
			if($val === false)
				continue;
			
			//Add Property and Value
			$props[] = $field;
			$vals[] = $val;
		}
		
		//Any Values?
		if(count($props) > 0)
		{
			do{
				//Get Value and Property
				$prop = array_shift($props);
				
				//Add to SQL
				$sql .= " $prop = ? ";
				
				//Add Comma if More Updates
				if(count($props))
				{
					$sql .= ",";
				}
			} while(count($props));
		}
		
		//Set WHERE Clause
		$vals[] = $this->id;
		$sql .= "WHERE id = ?";
		
		//Execute Statement
		return Porm::executeStatement($con, $sql, $vals);
	}
	
	/*
		Name:	delete
		Args:	void
		Retv:	The result of a PDO statement
		execution (boolean for success/failure)
		Desc:	Deletes the current object from
		the database.
	*/
	public function delete()
	{
		//Get Database Connection
		$con = Porm::getCon($this);
		
		//Get Table Name
		$table = PormConfig::getFullName($this);
		
		return Porm::executeStatement($con, "DELETE FROM $table WHERE id = ?", [$this->id]);
	}
	
	#Static Methods
	
	/*
		Name:	join
		Args:	string $sql,
				array $params = [],
				array $classes = []
		Retv:	Returns an array of PormJoin 
		objects or an empty array on failure
		Desc:	This function takes a SQL query
		that joins multiple tables, params to 
		bind, and the array of PormClass objects
		that are joined. It handles the results
		in a way that allows the individual
		PormClass objects to be extracted from
		the results.
	*/
	static function join($sql, $params = [], $classes = [])
	{
		//Get Class Name (for static functions)
		$class = get_called_class();
		
		//Get Database Connection
		$con = Porm::getCon(new $class);
		
		//Leading SQL Query
		$start = "SELECT ";
		
		//Get Column Names from Objects
		$classes[] = new $class;
		
		$fullColumns = [];
		$tables = [];
		
		foreach($classes as $class)
		{
			$table = PormConfig::getTableShort($class);
			
			$tables[] = "`$table`";
			
			foreach($class->fields as $field)
			{
				$fullColumns[] = "`$table`.`$field` AS `$table.$field`";
			}
		}
		
		//Build Query
		$start .= implode(", ", $fullColumns);
		
		$tables = implode(", ", $tables);
		
		$query = $start . " FROM $tables" . $sql;
		
		$results = Porm::readAll($con, $query , $params);
		
		for($i=0; $i < count($results); $i++)
		{
			$join = new PormJoin;
			$join->fields = get_object_vars($results[$i]);
			$results[$i] = $join;
		}
		
		return $results;
	}
	
	/*
		Name:	query
		Args:	string $sql, array $params = []
		Retv:	A result set array of objects of
		$this type. An empty array on failure.
		Desc:	Performs a generic database 
		read on the database from a complete
		SQL query and optional parameters to bind.
	*/
	static function query($sql, $params = [])
	{
		//Get Class Name (for static functions)
		$class = get_called_class();
		
		//Get Database Connection
		$con = Porm::getCon(new $class);
		
		//Read Results
		return Porm::readAll($con, $sql, $params, $class);
	}
	
	/*
		Name:	read
		Args:	mixed $sql, array $params = []
		Retv:	A result set array of objects of
		$this type. An empty array on failure.
		Desc:	Performs a read database 
		read on the database using a variety of different methods.
		SQL:
			When $sql is a partial SQL string,
			the query is performed by appending 
			the $sql to the end of an auto generated
			SELECT * FROM table statement. The partial
			SQL string will thus generally start with
			the WHERE clause.
		INT:
			When $sql is an integer, it is used
			to find the row with that unique id.
		ARRAY:
			When array is used, the array will have
			key-value pairs that match table column
			names with their values. It will return
			whatever results match all criteria.
			In the future, it may be possible to
			use non-keyed "OR" nad "AND" elements
			in the array to add more functionality.
	*/
	static function read($id_sql_arr = "", $params = [])
	{
		return self::fetch("read", $id_sql_arr, $params);
	}
	
	/*
		Name:	readAll
		Args:	same as above
		Retv:	one result object or NULL on failure
		Desc:	same as above
	*/
	static function readAll($id_sql_arr = "", $params = [])
	{
		return self::fetch("readAll", $id_sql_arr, $params);
	}
	
	#Private Static Methods
	
	/*
		Name:	arrayToSQL
		Args:	array $column_value
		Retv:	A list of SQL AND clauses as a 
		string with values replaced with ?s to
		bind.
		Desc:	Takes a key-value array and converts
		it to SQL conditions to be used in a WHERE 
		clause. The keys are column names and they
		are matched with their values. Only ANDs are
		used right now, but this may be expanded
		in the future.
	*/
	private static function arrayToSQL($arr)
	{
		$keys = array_keys($arr);
		
		$sql = "";
		foreach($keys as $key)
		{
			$sql .= " AND $key = ?";
		}
		return $sql;
	}
	
	/*
		Name:	fetch
		Args:	string $func, mixed $sql, array $params
		Retv:	A result set of objects of $this type
		Desc:	The function used by Porm internally 
		to fetch results from the Porm class. Not 
		intended to be used by the user.
	*/
	private static function fetch($porm_func, $id_sql_arr, $params = [])
	{
		//Get Class Name (for static functions)
		$class = get_called_class();
		
		//Get Database Connection
		$con = Porm::getCon(new $class);
		
		//Get Table Name
		$table = PormConfig::getFullName(new $class);

		$start_sql = "SELECT * FROM $table ";
		
		//Default Query
		if($id_sql_arr == "")
			$id_sql_arr = "WHERE 1";
		
		//Unique ID
		if(is_int($id_sql_arr))
		{
			return call_user_func(["Porm\Porm", $porm_func], $con, $start_sql . "WHERE id = ?", [$id_sql_arr], $class);
		}
		
		//SQL and Params
		else if(is_string($id_sql_arr))
		{
			return call_user_func(["Porm\Porm", $porm_func], $con, $start_sql . $id_sql_arr, $params, $class);
		}
		
		//Key-Value Array
		else if(is_array($id_sql_arr))
		{
			$sql = $start_sql . "WHERE 1" .  self::arrayToSQL($id_sql_arr);
			
			return call_user_func(["Porm\Porm", $porm_func], $con, $sql, array_values($id_sql_arr), $class);
		}
		
		//Error
		else
		{
			return [];
		}
	}
}

?>