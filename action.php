<?php

session_start();

// Script de déconnexion
if (isset($_POST['action']) && $_POST['action'] == 'leave') {
	require('database/UserModel.php');

	$user_object = new UserModel;
	$user_object->setUserId($_POST['user_id']);
	$user_object->setIsOnline(False);
	$user_object->setUserToken($_SESSION['user_data'][$_POST['user_id']]['token']);

	if ($user_object->update_user_login_data()) {
		unset($_SESSION['user_data']);
		session_destroy();
		echo json_encode(['status' => 1]);
	}
}

// Récupérer l'historique des discussions privées
if (isset($_POST["action"]) && $_POST["action"] == 'fetch_chat') {
	require 'database/MessageModel.php';

	$private_chat_object = new MessageModel;
	$private_chat_object->setFromUserId($_POST["to_user_id"]);
	$private_chat_object->setToUserId($_POST["from_user_id"]);

	echo json_encode($private_chat_object->get_all_chat_data());
}

// SELON CONSIGNES : Vérifier si l'utilisateur peut envoyer un message
if (isset($_POST["action"]) && $_POST["action"] == 'check_can_send') {
	require 'database/MessageModel.php';

	$message_object = new MessageModel;
	$message_object->setFromUserId($_POST["from_user_id"]);
	$message_object->setToUserId($_POST["to_user_id"]);

	$result = $message_object->can_user_send_message();
	echo json_encode($result);
}

// SELON CONSIGNES : Vérifier si un message spécifique a été annoté par un utilisateur
if (isset($_POST["action"]) && $_POST["action"] == 'check_message_annotation') {
	require 'database/MessageModel.php';

	$message_object = new MessageModel;
	$is_annotated = $message_object->is_message_annotated_by_user($_POST["message_id"], $_POST["user_id"]);
	
	echo json_encode(['is_annotated' => $is_annotated]);
}

// SELON CONSIGNES : Annoter un message via AJAX
if (isset($_POST["action"]) && $_POST["action"] == 'annotate_message') {
	require 'database/MessageModel.php';

	$message_object = new MessageModel;
	$message_object->setFromUserId($_POST["annotator_id"]);
	$timestamp = date('Y-m-d H:i:s');
	$message_object->setTimestamp($timestamp);

	$result = $message_object->annotate_received_message($_POST["message_id"], $_POST["emotion"]);
	echo json_encode($result);
}

?>