<?php

#Include Morph
include 'init.php';

$class = "";

if(isset($_POST["gen"]))
{
	$db = $_POST["db"];
	$table = $_POST["table"];
	
	$table_class = ucFirst($table);
	$code = "class $table_class extends MorphClass{\n\n\t#Stuff used by Morph\n\n\tpublic static \$dbName = '$db';\n\tpublic static \$tableName = '$table';\n";
	
	$morph = new Morph($db);
	$con = $morph->con;
	
	$cres = $con->query("SHOW COLUMNS IN `$db`.`$table`");
			
	if($cres)
	{
		$columns = [];
		foreach($cres as $column)
		{
			$name = $column[0];
			$columns[] = '"' . $name . '"';
			$code .= "\tpublic \${$name} = false;\n";
		}
		
		$code .= "\tpublic \$fields = [" . implode(', ',  $columns) . "];\n";
		
		$code .= "\n\t#Other stuff\n\n";
		
		$code .= "}\n";
		
		$class = $code;
	}
			
	else
	{
		$class = "Could not find database/table.";
	}
}

?>

<!doctype html>
<html>
<head>
<title>Generate Morph Classes</title>
</head>
<body>
<h1>Generate Morph Classes</h1>
<form method="post">
<h4>Database</h4>
<select name="db">
<?php
foreach(MorphConfig::$dbs as $dbname => $dbinfo)
{
	print "<option value='$dbname'>$dbname</option>";
}
?>
</select>
<h4>Table</h4>
<input name="table">
<h4>Generate Class</h4>
<input type="submit" name="gen" value="Generate">
</form>
<hr>
<?php
print "<pre class='prettyprint'>" . $class . "</pre>";
?>
</body>
<script src="https://cdn.rawgit.com/google/code-prettify/master/loader/run_prettify.js"></script>
</html>