<?php
// Database_connection.php

class Database_connection
{
	function connect()
	{
		$connect = new PDO("mysql:host=localhost; dbname=app_chat", "root", "");

		return $connect;
	}
}

?>