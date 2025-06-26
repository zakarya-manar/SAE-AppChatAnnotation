<?php

session_start(); // Démarre une session PHP pour suivre les données utilisateur

// Script de déconnexion
if (isset($_POST['action']) && $_POST['action'] == 'leave') {
	require('database/UserModel.php'); // Inclusion du modèle utilisateur

	$user_object = new UserModel;
	$user_object->setUserId($_POST['user_id']); // Définit l'ID de l'utilisateur à déconnecter
	$user_object->setIsOnline(False); // Mise à jour du statut : hors ligne
	$user_object->setUserToken($_SESSION['user_data'][$_POST['user_id']]['token']); // Récupère le token en session

	// Mise à jour en base et destruction de la session si succès
	if ($user_object->update_user_login_data()) {
		unset($_SESSION['user_data']); // Supprime les données utilisateur de la session
		session_destroy(); // Détruit la session
		echo json_encode(['status' => 1]); // Réponse JSON indiquant le succès
	}
}

// Récupérer l'historique des discussions privées
if (isset($_POST["action"]) && $_POST["action"] == 'fetch_chat') {
	require 'database/MessageModel.php'; // Inclusion du modèle de message

	$private_chat_object = new MessageModel;
	$private_chat_object->setFromUserId($_POST["to_user_id"]); // Utilisateur source (inversé pour affichage)
	$private_chat_object->setToUserId($_POST["from_user_id"]); // Utilisateur destination

	// Renvoie tous les messages échangés entre les deux utilisateurs
	echo json_encode($private_chat_object->get_all_chat_data());
}

// SELON CONSIGNES : Vérifier si l'utilisateur peut envoyer un message
if (isset($_POST["action"]) && $_POST["action"] == 'check_can_send') {
	require 'database/MessageModel.php';

	$message_object = new MessageModel;
	$message_object->setFromUserId($_POST["from_user_id"]); // Expéditeur
	$message_object->setToUserId($_POST["to_user_id"]);     // Destinataire

	// Vérifie si l'utilisateur a bien annoté tous les messages reçus
	$result = $message_object->can_user_send_message();
	echo json_encode($result); // Retourne le résultat (peut envoyer ou pas)
}

// SELON CONSIGNES : Vérifier si un message spécifique a été annoté par un utilisateur
if (isset($_POST["action"]) && $_POST["action"] == 'check_message_annotation') {
	require 'database/MessageModel.php';

	$message_object = new MessageModel;
	// Vérifie en base si l'utilisateur a bien annoté ce message
	$is_annotated = $message_object->is_message_annotated_by_user($_POST["message_id"], $_POST["user_id"]);
	
	echo json_encode(['is_annotated' => $is_annotated]); // Réponse binaire
}

// SELON CONSIGNES : Annoter un message via AJAX
if (isset($_POST["action"]) && $_POST["action"] == 'annotate_message') {
	require 'database/MessageModel.php';

	$message_object = new MessageModel;
	$message_object->setFromUserId($_POST["annotator_id"]); // Définit l’ID de l’annotateur
	$timestamp = date('Y-m-d H:i:s');                        // Timestamp de l’annotation
	$message_object->setTimestamp($timestamp);               // Enregistre le timestamp

	// Enregistre l’émotion annotée sur un message reçu
	$result = $message_object->annotate_received_message($_POST["message_id"], $_POST["emotion"]);
	echo json_encode($result); // Retourne le succès ou l’échec de l’opération
}

?>