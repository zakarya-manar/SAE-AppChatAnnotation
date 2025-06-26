<?php

class MessageModel
{
	// Attributs privés représentant les données d'un message
	private $message_id;
	private $to_user_id;
	private $from_user_id;
	private $content;
	private $timestamp;
	private $emotion;

	// Connexion à la base de données
	protected $connect;

	public function __construct()
	{
		// Inclut la classe de connexion à la base
		require_once('Database_connection.php');
		$db = new Database_connection();
		$this->connect = $db->connect();
	}

	// --- GETTERS ET SETTERS POUR CHAQUE ATTRIBUT ---

	function setEmotion($emotion) { $this->emotion = $emotion; }
	function getEmotion() { return $this->emotion; }

	function getMessageId() { return $this->message_id; }
	function setMessageId($message_id) { $this->message_id = $message_id; }

	function setToUserId($to_user_id) { $this->to_user_id = $to_user_id; }
	function getToUserId() { return $this->to_user_id; }

	function setFromUserId($from_user_id) { $this->from_user_id = $from_user_id; }
	function getFromUserId() { return $this->from_user_id; }

	function setChatMessage($chat_message) { $this->content = $chat_message; }
	function getChatMessage() { return $this->content; }

	function setTimestamp($timestamp) { $this->timestamp = $timestamp; }
	function getTimestamp() { return $this->timestamp; }

	// --- LOGIQUE DE CONTRÔLE : Peut-on envoyer un message ? ---
	function can_user_send_message()
	{
		// Récupère le dernier message échangé entre les deux utilisateurs
		$query = "SELECT m.message_id, m.from_user_id, m.to_user_id 
				  FROM Message m
				  WHERE (m.from_user_id = :from_user_id AND m.to_user_id = :to_user_id)
					 OR (m.from_user_id = :to_user_id AND m.to_user_id = :from_user_id)
				  ORDER BY m.message_id DESC 
				  LIMIT 1";

		$stmt = $this->connect->prepare($query);
		$stmt->bindParam(':from_user_id', $this->from_user_id);
		$stmt->bindParam(':to_user_id', $this->to_user_id);
		$stmt->execute();

		$last_message = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$last_message) {
			// Aucun message : autorisé à envoyer
			return ['can_send' => true, 'reason' => ''];
		}

		// Si le dernier message vient de l'utilisateur actuel : il doit attendre
		if ($last_message['from_user_id'] == $this->from_user_id) {
			return [
				'can_send' => false, 
				'reason' => 'Vous devez attendre que votre correspondant annote votre message et vous réponde. C\'est à son tour de parler.'
			];
		}

		// Sinon, vérifier s'il a annoté le dernier message reçu
		$annotation_query = "SELECT COUNT(*) as count 
							 FROM Annotation 
							 WHERE message_id = :message_id 
							   AND annotator_id = :annotator_id";

		$annotation_stmt = $this->connect->prepare($annotation_query);
		$annotation_stmt->bindParam(':message_id', $last_message['message_id']);
		$annotation_stmt->bindParam(':annotator_id', $this->from_user_id);
		$annotation_stmt->execute();
		$annotation_result = $annotation_stmt->fetch(PDO::FETCH_ASSOC);

		if ($annotation_result['count'] == 0) {
			// Doit d'abord annoter
			return [
				'can_send' => false, 
				'reason' => 'Vous devez d\'abord annoter le message reçu avant de pouvoir envoyer votre réponse.',
				'message_to_annotate' => $last_message['message_id']
			];
		}

		// Sinon, autorisé à envoyer
		return ['can_send' => true, 'reason' => ''];
	}

	// --- Annoter un message reçu ---
	function annotate_received_message($message_id, $emotion)
	{
		try {
			$annotation_query = "INSERT INTO Annotation (message_id, annotator_id, emotion, created_at)
								 VALUES (:message_id, :annotator_id, :emotion, :created_at)";
			
			$annotation_stmt = $this->connect->prepare($annotation_query);
			$annotation_stmt->bindParam(':message_id', $message_id);
			$annotation_stmt->bindParam(':annotator_id', $this->from_user_id);
			$annotation_stmt->bindParam(':emotion', $emotion);
			$annotation_stmt->bindParam(':created_at', $this->timestamp);
			
			if ($annotation_stmt->execute()) {
				return ['success' => true, 'message' => 'Message annoté avec succès.'];
			} else {
				return ['success' => false, 'message' => 'Erreur lors de l\'annotation.'];
			}

		} catch (Exception $e) {
			return ['success' => false, 'message' => 'Erreur lors de l\'annotation : ' . $e->getMessage()];
		}
	}

	// --- Récupérer les messages reçus non encore annotés par l'utilisateur ---
	function get_unannotated_received_messages()
	{
		$query = "SELECT m.message_id, m.content, m.timestamp, u.username as sender_name, m.from_user_id
				  FROM Message m
				  JOIN User u ON m.from_user_id = u.user_id
				  LEFT JOIN Annotation a ON (m.message_id = a.message_id AND a.annotator_id = :user_id)
				  WHERE m.to_user_id = :user_id 
					AND a.annotation_id IS NULL
				  ORDER BY m.timestamp ASC";

		$stmt = $this->connect->prepare($query);
		$stmt->bindParam(':user_id', $this->from_user_id);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	// --- Récupérer l'historique complet des messages entre deux utilisateurs ---
	function get_all_chat_data()
	{
		$query = "SELECT a.username as from_username, b.username as to_username, 
						 m.message_id, m.content, m.timestamp, m.to_user_id, m.from_user_id
				  FROM Message m
				  INNER JOIN User a ON m.from_user_id = a.user_id
				  INNER JOIN User b ON m.to_user_id = b.user_id
				  WHERE (m.from_user_id = :from_user_id AND m.to_user_id = :to_user_id)
					 OR (m.from_user_id = :to_user_id AND m.to_user_id = :from_user_id)
				  ORDER BY m.timestamp ASC";

		$stmt = $this->connect->prepare($query);
		$stmt->bindParam(':from_user_id', $this->from_user_id);
		$stmt->bindParam(':to_user_id', $this->to_user_id);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	// --- Enregistrer un nouveau message ---
	function save_chat()
	{
		$query = "INSERT INTO Message (to_user_id, from_user_id, content, timestamp) 
				  VALUES (:to_user_id, :from_user_id, :content, :timestamp)";

		$stmt = $this->connect->prepare($query);
		$stmt->bindParam(':to_user_id', $this->to_user_id);
		$stmt->bindParam(':from_user_id', $this->from_user_id);
		$stmt->bindParam(':content', $this->content);
		$stmt->bindParam(':timestamp', $this->timestamp);

		if ($stmt->execute()) {
			$this->message_id = $this->connect->lastInsertId(); // Récupère l’ID du message inséré
			return true;
		}
		return false;
	}

	// --- Enregistrer l'annotation de l'expéditeur en même temps que son message ---
	function save_sender_annotation()
	{
		$query = "INSERT INTO Annotation (message_id, annotator_id, emotion, created_at)
				  VALUES (:message_id, :annotator_id, :emotion, :created_at)";

		$stmt = $this->connect->prepare($query);
		$stmt->bindParam(':message_id', $this->message_id);
		$stmt->bindParam(':annotator_id', $this->from_user_id);
		$stmt->bindParam(':emotion', $this->emotion);
		$stmt->bindParam(':created_at', $this->timestamp);
		
		return $stmt->execute();
	}

	// --- Vérifie si un message est déjà annoté par un utilisateur donné ---
	function is_message_annotated_by_user($message_id, $user_id)
	{
		$query = "SELECT COUNT(*) as count FROM Annotation 
				  WHERE message_id = :message_id AND annotator_id = :user_id";
		
		$stmt = $this->connect->prepare($query);
		$stmt->bindParam(':message_id', $message_id);
		$stmt->bindParam(':user_id', $user_id);
		$stmt->execute();
		
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		return $result['count'] > 0;
	}
}

?>
