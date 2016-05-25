<?php

/*
	Author:	Sheldon Juncker
	Date:	4/3/2016
	Desc:	
	This is a class for the results of queries
	joined with Morph.
	
	The class will hold all of the fields from 
	a join query and has a get method for getting
	the data for each joined table as an object.
*/

class MorphJoin
{
	//Combined table fields from query
	public $fields = [];
	
	/*
		Name:	get
		Args:	MorphClass $object
		Retv:	The MorphClass $object with filled
		properties from the joined result.
		Desc:	The get method is used to extract an 
		object from a SQL query that joins
		multiple tables.
	*/
	public function get($object)
	{
		//Get table name
		$table = $object::tableName;
		
		//Get the fields that apply to this table
		foreach($this->fields as $f => $v)
		{
			//Array: 0 = table name, 1 = column name
			$table_column = explode('.', $f);
			
			//Add field if match
			if($table_column[0] == $table)
			{
				$object->{$table_column[1]} = $v;
			}
		}
		
		return $object;
	}
}

?>