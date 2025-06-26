<?php
// Gestionnaire WebSocket selon consignes du professeur

namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

// Inclusion des modèles pour gestion des utilisateurs et des messages
require dirname(__DIR__) . "/database/UserModel.php";
require dirname(__DIR__) . "/database/MessageModel.php";

class Chat implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        // Initialisation de la liste des clients connectés
        $this->clients = new \SplObjectStorage;
        echo 'Server Started - Annotation System According to Professor Requirements';
    }

    public function onOpen(ConnectionInterface $conn) {
        // Lorsqu'un utilisateur se connecte, on l'ajoute à la liste
        $this->clients->attach($conn);

        // Récupération du token dans la query string pour authentification
        $querystring = $conn->httpRequest->getUri()->getQuery();
        parse_str($querystring, $queryarray);

        if (isset($queryarray['token'])) {
            // Mise à jour du statut et de l'ID de connexion utilisateur
            $user_object = new \UserModel;
            $user_object->setUserToken($queryarray['token']);
            $user_object->setUserConnectionId($conn->resourceId);
            $user_object->update_user_connection_id();
            $user_data = $user_object->get_user_id_from_token();
            $user_id = $user_data['user_id'];

            // Préparation des données de statut à diffuser aux autres clients
            $data['user_id'] = $user_id;
            $data['status_type'] = 'Online';

            foreach ($this->clients as $client) {
                $client->send(json_encode($data));
            }
        }
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        // Si action = annotation d'un message reçu
        if (isset($data['action']) && $data['action'] === 'annotate_message') {
            $this->handleAnnotation($from, $data);
            return;
        }

        // Si envoi d’un nouveau message annoté par l’expéditeur
        if (isset($data['userId']) && isset($data['receiver_userid']) && isset($data['msg']) && isset($data['emotion'])) {
            $this->handleMessageWithAnnotation($from, $data);
            return;
        }
    }

    private function handleMessageWithAnnotation(ConnectionInterface $from, $data) {
        $message_object = new \MessageModel;
        
        $message_object->setToUserId($data['receiver_userid']);
        $message_object->setFromUserId($data['userId']);
        
        // Vérifie si l'utilisateur a annoté tous les messages reçus
        $can_send_result = $message_object->can_user_send_message();
        
        if (!$can_send_result['can_send']) {
            // Envoi refusé : renvoyer l’erreur et message à annoter
            $error_data = [
                'error' => 'annotation_required',
                'reason' => $can_send_result['reason'],
                'message_to_annotate' => isset($can_send_result['message_to_annotate']) ? $can_send_result['message_to_annotate'] : null
            ];
            
            $from->send(json_encode($error_data));
            return;
        }

        // Si validation réussie, préparer l'envoi
        $message_object->setChatMessage($data['msg']);
        $message_object->setEmotion($data['emotion']);
        $timestamp = date('Y-m-d H:i:s');
        $message_object->setTimestamp($timestamp);

        // Sauvegarde du message dans la base de données
        if ($message_object->save_chat()) {
            // Sauvegarde de l’annotation de l’émetteur
            $message_object->save_sender_annotation();

            $user_object = new \UserModel;

            // Récupération des données de l’émetteur
            $user_object->setUserId($data['userId']);
            $sender_user_data = $user_object->get_user_data_by_id();
            $sender_username = $sender_user_data['username'];

            // Récupération des données du destinataire
            $user_object->setUserId($data['receiver_userid']);
            $receiver_user_data = $user_object->get_user_data_by_id();
            $receiver_user_connection_id = $receiver_user_data['user_connection_id'];

            // Préparer les données à envoyer
            $response_data = [
                'userId' => $data['userId'],
                'receiver_userid' => $data['receiver_userid'],
                'msg' => $data['msg'],
                'emotion' => $data['emotion'],
                'datetime' => $timestamp,
                'message_id' => $message_object->getMessageId()
            ];

            // Envoi aux deux utilisateurs impliqués (émetteur + récepteur)
            foreach ($this->clients as $client) {
                $response_data['from'] = ($from == $client) ? 'Me' : $sender_username;

                if ($client->resourceId == $receiver_user_connection_id || $from == $client) {
                    $client->send(json_encode($response_data));
                }
            }
        }
    }

    private function handleAnnotation(ConnectionInterface $from, $data) {
        $message_object = new \MessageModel;
        
        $message_object->setFromUserId($data['annotator_id']);
        $timestamp = date('Y-m-d H:i:s');
        $message_object->setTimestamp($timestamp);
        
        // Appelle la fonction pour annoter un message reçu
        $result = $message_object->annotate_received_message($data['message_id'], $data['emotion']);
        
        if ($result['success']) {
            // Retourner un message de succès
            $response_data = [
                'action' => 'annotation_success',
                'message' => $result['message'],
                'message_id' => $data['message_id']
            ];
            $from->send(json_encode($response_data));
        } else {
            // Retourner un message d'erreur
            $error_data = [
                'action' => 'annotation_error',
                'message' => $result['message']
            ];
            $from->send(json_encode($error_data));
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // Lorsqu’un utilisateur se déconnecte
        $querystring = $conn->httpRequest->getUri()->getQuery();
        parse_str($querystring, $queryarray);

        if (isset($queryarray['token'])) {
            $user_object = new \UserModel;
            $user_object->setUserToken($queryarray['token']);
            $user_data = $user_object->get_user_id_from_token();
            $user_id = $user_data['user_id'];

            // Informer les autres utilisateurs que celui-ci est hors ligne
            $data['user_id'] = $user_id;
            $data['status_type'] = 'Offline';

            foreach ($this->clients as $client) {
                $client->send(json_encode($data));
            }
        }

        // Retirer le client de la liste
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        // Gestion des erreurs WebSocket
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

?>