<?php

class UserModel
{
	// Attributs correspondant aux colonnes de la table User
	private $user_id;
	private $username;
	private $email;
	private $password_hash;
	private $created_at;
	private $is_online;
	private $user_token;
	private $user_connection_id;

	public $connect; // Objet de connexion PDO

	public function __construct()
	{
		// Connexion à la base via Database_connection
		require_once('Database_connection.php');
		$database_object = new Database_connection;
		$this->connect = $database_object->connect();
	}

	// --- GETTERS ET SETTERS CLASSIQUES POUR CHAQUE ATTRIBUT ---

	function setUserId($user_id) { $this->user_id = $user_id; }
	function getUserId() { return $this->user_id; }

	function setUsername($username) { $this->username = $username; }
	function getUsername() { return $this->username; }

	function setEmail($email) { $this->email = $email; }
	function getEmail() { return $this->email; }

	function setPasswordHash($password_hash) { $this->password_hash = $password_hash; }
	function getPasswordHash() { return $this->password_hash; }

	function setCreatedAt($created_at) { $this->created_at = $created_at; }
	function getCreatedAt() { return $this->created_at; }

	function setIsOnline($is_online) { $this->is_online = $is_online; }
	function getIsOnline() { return $this->is_online; }

	function setUserToken($user_token) { $this->user_token = $user_token; }
	function getUserToken() { return $this->user_token; }

	function setUserConnectionId($user_connection_id) { $this->user_connection_id = $user_connection_id; }
	function getUserConnectionId() { return $this->user_connection_id; }

	// --- GESTION SÉCURISÉE DES MOTS DE PASSE ---

	function hashPassword($plain_password) {
		return password_hash($plain_password, PASSWORD_DEFAULT);
	}

	function verifyPassword($plain_password, $hashed_password) {
		return password_verify($plain_password, $hashed_password);
	}

	// --- CONTRÔLE D'UNICITÉ POUR LE NOM D'UTILISATEUR ---

	function username_exists($username)
	{
		$query = "SELECT COUNT(*) as count FROM User WHERE username = :username";
		$stmt = $this->connect->prepare($query);
		$stmt->bindParam(':username', $username);
		$stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		return $result['count'] > 0;
	}

	// --- CONTRÔLE D'UNICITÉ POUR L'EMAIL ---

	function email_exists()
	{
		$query = "SELECT COUNT(*) as count FROM User WHERE email = :email";
		$stmt = $this->connect->prepare($query);
		$stmt->bindParam(':email', $this->email);
		$stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		return $result['count'] > 0;
	}

	// --- RÉCUPÉRATION DES DONNÉES UTILISATEUR PAR EMAIL ---

	function get_user_data_by_email()
	{
		$query = "SELECT * FROM User WHERE email = :email ORDER BY is_online DESC";
		$stmt = $this->connect->prepare($query);
		$stmt->bindParam(':email', $this->email);

		if ($stmt->execute()) {
			$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
			return $user_data;
		}
		return false;
	}

	// --- CRÉATION D'UN NOUVEL UTILISATEUR ---

	function save_data()
	{
		// Vérification unicité
		if ($this->username_exists($this->username)) {
			return ['success' => false, 'message' => 'Ce nom d\'utilisateur est déjà pris. Veuillez en choisir un autre.'];
		}

		if ($this->email_exists()) {
			return ['success' => false, 'message' => 'Cette adresse email est déjà utilisée.'];
		}

		$query = "INSERT INTO User (username, email, password_hash, created_at, is_online) 
				  VALUES (:username, :email, :password_hash, :created_at, :is_online)";
		
		$stmt = $this->connect->prepare($query);

		$hashed_password = $this->hashPassword($this->password_hash);
		$is_online = false;
		
		$stmt->bindParam(':username', $this->username);
		$stmt->bindParam(':email', $this->email);
		$stmt->bindParam(':password_hash', $hashed_password);
		$stmt->bindParam(':created_at', $this->created_at);
		$stmt->bindParam(':is_online', $is_online);

		try {
			if ($stmt->execute()) {
				return ['success' => true, 'message' => 'Compte créé avec succès.'];
			} else {
				return ['success' => false, 'message' => 'Erreur lors de la création du compte.'];
			}
		} catch (PDOException $e) {
			return ['success' => false, 'message' => 'Erreur base de données : ' . $e->getMessage()];
		}
	}

	// --- MET À JOUR LE STATUT EN LIGNE + TOKEN UTILISATEUR ---

	function update_user_login_data()
	{
		$query = "UPDATE User 
				  SET is_online = :is_online, user_token = :user_token  
				  WHERE user_id = :user_id";

		$stmt = $this->connect->prepare($query);
		$stmt->bindParam(':is_online', $this->is_online);
		$stmt->bindParam(':user_token', $this->user_token);
		$stmt->bindParam(':user_id', $this->user_id);

		return $stmt->execute();
	}

	// --- RÉCUPÈRE LES INFOS D'UN UTILISATEUR PAR ID ---

	function get_user_data_by_id()
	{
		$query = "SELECT * FROM User WHERE user_id = :user_id ORDER BY is_online DESC";
		$stmt = $this->connect->prepare($query);
		$stmt->bindParam(':user_id', $this->user_id);

		try {
			if ($stmt->execute()) {
				$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
				return $user_data ? $user_data : array();
			} else {
				return array();
			}
		} catch (Exception $error) {
			echo $error->getMessage();
			return array();
		}
	}

	// --- MET À JOUR LE MOT DE PASSE UNIQUEMENT ---

	function update_password()
	{
		$query = "UPDATE User 
				  SET password_hash = :password_hash 
				  WHERE user_id = :user_id";

		$stmt = $this->connect->prepare($query);
		$hashed_password = $this->hashPassword($this->password_hash);
		$stmt->bindParam(':password_hash', $hashed_password);
		$stmt->bindParam(':user_id', $this->user_id);

		return $stmt->execute();
	}

	// --- MET À JOUR LE NOM D'UTILISATEUR UNIQUEMENT ---

	function update_username()
	{
		$query = "UPDATE User 
				  SET username = :username 
				  WHERE user_id = :user_id";

		$stmt = $this->connect->prepare($query);
		$stmt->bindParam(':username', $this->username);
		$stmt->bindParam(':user_id', $this->user_id);

		return $stmt->execute();
	}

	// --- MET À JOUR TOUTES LES DONNÉES : nom, email, mot de passe ---

	function update_data()
	{
		$query = "UPDATE User 
				  SET username = :username, email = :email, password_hash = :password_hash 
				  WHERE user_id = :user_id";

		$stmt = $this->connect->prepare($query);
		$hashed_password = $this->hashPassword($this->password_hash);
		
		$stmt->bindParam(':username', $this->username);
		$stmt->bindParam(':email', $this->email);
		$stmt->bindParam(':password_hash', $hashed_password);
		$stmt->bindParam(':user_id', $this->user_id);

		return $stmt->execute();
	}

	// --- RÉCUPÈRE TOUS LES UTILISATEURS POUR AFFICHAGE LISTE ---

	function get_user_all_data()
	{
		$query = "SELECT * FROM User ORDER BY is_online DESC";
		$stmt = $this->connect->prepare($query);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	// --- MET À JOUR L’ID DE CONNEXION SOCKET SELON LE TOKEN ---

	function update_user_connection_id()
	{
		$query = "UPDATE User 
				  SET user_connection_id = :user_connection_id 
				  WHERE user_token = :user_token";

		$stmt = $this->connect->prepare($query);
		$stmt->bindParam(':user_connection_id', $this->user_connection_id);
		$stmt->bindParam(':user_token', $this->user_token);
		$stmt->execute();
	}

	// --- RETOURNE L’ID UTILISATEUR À PARTIR D’UN TOKEN ---

	function get_user_id_from_token()
	{
		$query = "SELECT user_id FROM User WHERE user_token = :user_token";
		$stmt = $this->connect->prepare($query);
		$stmt->bindParam(':user_token', $this->user_token);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
}

?>
