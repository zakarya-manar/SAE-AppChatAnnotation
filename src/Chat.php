<?php
// Gestionnaire WebSocket selon consignes du professeur

namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
require dirname(__DIR__) . "/database/UserModel.php";
require dirname(__DIR__) . "/database/MessageModel.php";

class Chat implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        echo 'Server Started - Annotation System According to Professor Requirements';
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);

        // Gestion du statut en ligne
        $querystring = $conn->httpRequest->getUri()->getQuery();
        parse_str($querystring, $queryarray);

        if (isset($queryarray['token'])) {
            $user_object = new \UserModel;
            $user_object->setUserToken($queryarray['token']);
            $user_object->setUserConnectionId($conn->resourceId);
            $user_object->update_user_connection_id();
            $user_data = $user_object->get_user_id_from_token();
            $user_id = $user_data['user_id'];

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
        
        // Vérifier si c'est une requête d'annotation
        if (isset($data['action']) && $data['action'] === 'annotate_message') {
            $this->handleAnnotation($from, $data);
            return;
        }

        // Vérifier si c'est un message normal avec annotation
        if (isset($data['userId']) && isset($data['receiver_userid']) && isset($data['msg']) && isset($data['emotion'])) {
            $this->handleMessageWithAnnotation($from, $data);
            return;
        }
    }

    private function handleMessageWithAnnotation(ConnectionInterface $from, $data) {
        $message_object = new \MessageModel;
        
        $message_object->setToUserId($data['receiver_userid']);
        $message_object->setFromUserId($data['userId']);
        
        // VÉRIFICATION SELON CONSIGNES : Peut-on envoyer ?
        $can_send_result = $message_object->can_user_send_message();
        
        if (!$can_send_result['can_send']) {
            // Bloquer l'envoi
            $error_data = [
                'error' => 'annotation_required',
                'reason' => $can_send_result['reason'],
                'message_to_annotate' => isset($can_send_result['message_to_annotate']) ? $can_send_result['message_to_annotate'] : null
            ];
            
            $from->send(json_encode($error_data));
            return;
        }

        // SELON CONSIGNES : L'utilisateur peut envoyer
        $message_object->setChatMessage($data['msg']);
        $message_object->setEmotion($data['emotion']);
        $timestamp = date('Y-m-d H:i:s');
        $message_object->setTimestamp($timestamp);

        // 1. Sauvegarder le message
        if ($message_object->save_chat()) {
            
            // 2. Sauvegarder l'annotation de l'expéditeur
            $message_object->save_sender_annotation();

            $user_object = new \UserModel;

            // Obtenir les données de l'expéditeur
            $user_object->setUserId($data['userId']);
            $sender_user_data = $user_object->get_user_data_by_id();
            $sender_username = $sender_user_data['username'];

            // Obtenir les données du destinataire
            $user_object->setUserId($data['receiver_userid']);
            $receiver_user_data = $user_object->get_user_data_by_id();
            $receiver_user_connection_id = $receiver_user_data['user_connection_id'];

            $response_data = [
                'userId' => $data['userId'],
                'receiver_userid' => $data['receiver_userid'],
                'msg' => $data['msg'],
                'emotion' => $data['emotion'], // Annotation de l'expéditeur
                'datetime' => $timestamp,
                'message_id' => $message_object->getMessageId()
            ];

            // Envoyer le message aux participants
            foreach ($this->clients as $client) {
                if ($from == $client) {
                    $response_data['from'] = 'Me';
                } else {
                    $response_data['from'] = $sender_username;
                }

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
        
        // Annoter le message reçu
        $result = $message_object->annotate_received_message($data['message_id'], $data['emotion']);
        
        if ($result['success']) {
            // Informer le client que l'annotation a réussi
            $response_data = [
                'action' => 'annotation_success',
                'message' => $result['message'],
                'message_id' => $data['message_id']
            ];
            
            $from->send(json_encode($response_data));
            
        } else {
            // Informer le client de l'erreur
            $error_data = [
                'action' => 'annotation_error',
                'message' => $result['message']
            ];
            
            $from->send(json_encode($error_data));
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $querystring = $conn->httpRequest->getUri()->getQuery();
        parse_str($querystring, $queryarray);

        if (isset($queryarray['token'])) {
            $user_object = new \UserModel;
            $user_object->setUserToken($queryarray['token']);
            $user_data = $user_object->get_user_id_from_token();
            $user_id = $user_data['user_id'];

            $data['user_id'] = $user_id;
            $data['status_type'] = 'Offline';

            foreach ($this->clients as $client) {
                $client->send(json_encode($data));
            }
        }

        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

?>