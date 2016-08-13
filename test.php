<style>
body{
	margin-left:100px;
	margin-top:25px;
}

pass, fail{
	display:block;
	font-size:25px;
	margin-bottom:10px;
}

pass{
	color:green;
}

fail{
	color:red;
}
</style>

<h1>Testing Morph</h1>

<?php

function pass($msg)
{
	print '<pass>' . $msg . '...</pass>';
}

function fail($msg)
{
	die('<fail>' . $msg . '</fail>');
}

require 'init.php';

pass('found morph');

//Test default connection
$default = MorphConfig::$default;

//Create dummy class
class MorphTest extends MorphClass
{
	public $dbName;
	public $tableName = "_MorphTest_1994";
	public $id;
	public $name;
	public $fields = ['id', 'name'];
	
	public function __construct()
	{
		$this->dbName = $GLOBALS["default"];
	}
}

//Create and set table/db info
$test = new MorphTest;
$test->dbName = $default;

//Test connection
try
{
	$con = Morph::getPDO($test);
	
	//Database not found
	if($con == NULL)
	{
		fail("could not find database `$default`");
	}
	
	else
	{
		pass('connected to the database');
	}
}

catch(PDOException $e)
{
	print "<fail>could not connect to default database `$default`: {$e->getMessage()}</fail>";
}

//Create a dummy table

$fullName = $test->getFullName();

$result = $con->exec($sql = "
DROP TABLE IF EXISTS $fullName
");

if($result === false)
{
	fail('failed to execute query: ' . "<pre>$sql</pre>");
}

$result = $con->exec($sql = "
CREATE TABLE $fullName(
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	name VARCHAR(64) NOT NULL
)");

if($result === false)
{
	fail('failed to execute query: ' . "<pre>$sql</pre>");
}

pass('created dummy table');


#Test CRUD

//Create
$bob = new MorphTest;
$bob->name = "Bob";

if($bob->create())
{
	pass('created a row');
}

else
{
	fail('could not create a row');
}

//Read
$bobs = MorphTest::readAll();

if(count($bobs) == 1 and $bobs[0] == $bob)
{
	pass('read that same row');
}

else
{
	fail('failed to read the row previoiusly created');
}

//Update
$bob->name = "BOB";
if($bob->update())
{
	//Read
	$bobs = MorphTest::readAll();

	if(count($bobs) == 1 and $bobs[0] == $bob)
	{
		pass('updated the row');
	}
	
	else
	{
		fail('could not update the row');
	}
}

else
{
	fail('could not update the row');
}

//Delete
if($bob->delete() and count(MorphTest::readAll()) == 0)
{
	pass('deleted the row');
}

else
{
	fail('could not delete the wor');
}


//Cleanup
if($con->exec("DROP TABLE IF EXISTS $fullName") !== false)
{
	pass('dropped the dummy table');
}

else
{
	fail('unable to drop dummy table');
}

$con = NULL;
pass('closed the connection');
print "Passed all tests!";
?>