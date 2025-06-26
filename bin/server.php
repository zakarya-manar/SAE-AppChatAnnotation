<?php
// Ce fichier est utilisé pour démarrer le serveur WebSocket à partir du terminal/de la ligne de commande (tout comme lorsque vous démarrez le serveur web de développement intégré de PHP en utilisant la commande « php -Shost local:8000 »).

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\Chat;

    require dirname(__DIR__) . '/vendor/autoload.php';

    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new Chat() // Voici la classe Chat.php de notre gestionnaire WebSocket personnalisé
            )
        ),
        8080 // le numéro de port du serveur WebSocket (c'est-à-dire le port sur lequel le serveur écoutera)
    );

    $server->run();
?>