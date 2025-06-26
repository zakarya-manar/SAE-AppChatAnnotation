<?php
// Database_connection.php

// Classe utilisée pour établir une connexion à la base de données MySQL via PDO
class Database_connection
{
	function connect()
	{
		// Connexion à la base de données "app_chat" sur localhost
		// Utilisateur : root | Mot de passe : vide
		$connect = new PDO("mysql:host=localhost; dbname=app_chat", "root", "");

		// Retourne l'objet de connexion PDO
		return $connect;
	}
}

?>