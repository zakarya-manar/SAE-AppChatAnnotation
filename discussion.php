<?php
// Démarrage de la session
session_start();

// Redirection si l'utilisateur n'est pas connecté
if (!isset($_SESSION['user_data'])) {
    header('location:index.php');
}

// Inclusion du modèle utilisateur
require('database/UserModel.php');

// Récupération du token utilisateur à partir de la session
foreach ($_SESSION['user_data'] as $key => $value) {
    $token = $value['token'];
}

// Création d'une instance du modèle utilisateur
$user_object = new UserModel;

// Récupération de tous les utilisateurs depuis la base
$user_data = $user_object->get_user_all_data();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Discussions</title>
    <link rel="icon" type="image/x-icon" href="img/bubble-chat.png">
    <link rel="stylesheet" href="style_discussion.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="vendor-front/jquery/jquery.min.js"></script>
    <script type="text/javascript" src="vendor-front/parsley/dist/parsley.min.js"></script>
    
    <style>
    /* Styles selon consignes du prof */
    .annotation-required {
        background-color: #fff3cd !important;
        border: 2px solid #ffc107 !important;
    }
    
    .annotation-notification {
        background-color: #d1ecf1;
        color: #0c5460;
        padding: 10px;
        border-radius: 5px;
        margin: 10px 0;
        border: 1px solid #bee5eb;
    }
    
    .message-waiting-annotation {
        background-color: #fff3cd !important;
        border-left: 4px solid #ffc107 !important;
    }
    
    .annotation-buttons {
        display: flex;
        gap: 5px;
        margin-top: 5px;
        flex-wrap: wrap;
    }
    
    .emotion-btn {
        padding: 5px 10px;
        border: none;
        border-radius: 15px;
        cursor: pointer;
        font-size: 12px;
        background-color: #007bff;
        color: white;
    }
    
    .emotion-btn:hover {
        background-color: #0056b3;
    }
    
    .send-button-disabled {
        background-color: #6c757d !important;
        cursor: not-allowed !important;
    }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar" id="sidebar">
            <div>
                <img src="img/profile.png" alt="profil" class="user_icon">
                <?php 
                foreach ($user_data as $user) {
                    if ($user['user_token'] === $token) {
                        echo "<p style='margin-top: 0px; color: white; font-size: 16px; font-weight: 500; text-align: center; font-family: 'Poppins', sans-serif;'>" . htmlspecialchars($user['username']) . '</p>';
                    }
                }
                ?>
            </div>
            <ul class="menu">
                <li class="menu-item active">
                    <a href="discussion.php"><img src="img/chat.png" alt="Messages Icon"></a>
                </li>
                <li class="menu-item">
                    <a href="profile.php"><img src="img/settings.png" alt="Settings Icon"></a>
                </li>
            </ul>
            <div class="logout">
                <a id="logout"><img src="img/logout.png"/></a>
            </div>
        </div>

        <div class="main-container" style="font-family: 'Poppins';">
            <div class="main-content" style="background: linear-gradient(to bottom, #f306d3, #6a11cb, #21fcff);">
                <div class="box" style="height: 100vh; flex: 0.35">
                    <?php
                    $login_user_id = '';
                    foreach ($_SESSION['user_data'] as $key => $value) {
                        $login_user_id = $value['id'];
                        $token = $value['token'];
                    ?>
                        <input type="hidden" name="login_user_id" id="login_user_id" value="<?php echo $login_user_id; ?>" />
                        <input type="hidden" name="is_active_chat" id="is_active_chat" value="No" />
                        <div>
                            <h2>Utilisateurs</h2>
                        </div>
                    <?php } ?>

                    <div class="list-group" style="max-height: 80vh; overflow-y: auto; -webkit-overflow-scrolling: touch;">
                    <?php
                    foreach ($user_data as $key => $user) {
                        $icon = '<i class="offline"></i>';
                        if ($user['is_online']) {
                            $icon = '<i class="online"></i>';
                        }

                        if ($user['user_id'] != $login_user_id) {
                            echo "
                                <a class='list-group-item list-group-item-action select_user' style='cursor:pointer' data-userid = '" . $user['user_id'] . "'>
                                    <img src='img/profile.png' width='50' />
                                    <span>
                                        <strong>
                                            <span id='list_username_" . $user["user_id"] . "'>" . htmlspecialchars($user['username']) . "</span>
                                        </strong>
                                    </span>
                                    <span id='userstatus_" . $user['user_id'] . "'>" . $icon . "</span>
                                </a>
                            ";
                        }
                    }
                    ?>
                    </div>
                </div>
                
                <div class="box" style="background-color: white;">
                    <h2>Discussions</h2>
                    <hr />
                    
                    <!-- Zone de notification -->
                    <div id="annotation_notifications" style="display: none;"></div>
                    
                    <br />
                    <div id="chat_area"></div> 
                </div>
            </div>
        </div>
    </div>

<script type="text/javascript">
$(document).ready(function(){
    var receiver_userid = '';
    
    // Connexion WebSocket
    var conn = new WebSocket('ws://localhost:8080?token=<?php echo $token; ?>');

    conn.onopen = function(event) {
        console.log('Connection Established!');
    };
    
    conn.onmessage = function(event) {
        var data = JSON.parse(event.data); 
        console.log(data);

        // Gestion des erreurs d'annotation - JEU DE RÔLE
        if (data.error === 'annotation_required') {
            showNotification(data.reason);
            updateSendButtonState(false);
            
            if (data.message_to_annotate) {
                highlightMessageToAnnotate(data.message_to_annotate);
            }
            return;
        }

        // Gestion des succès d'annotation
        if (data.action === 'annotation_success') {
            showNotification('Message annoté avec succès ! Vous pouvez maintenant envoyer un message.');
            removeAnnotationButtons();
            updateSendButtonState(true);
            return;
        }

        if (!data.error) {
            // Gestion du statut en ligne/hors ligne
            if (data.status_type == 'Online') {
                $('#userstatus_' + data.user_id).html('<i class="online"></i>'); 
            }
            else if (data.status_type == 'Offline') {
                $('#userstatus_' + data.user_id).html('<i class="offline"></i>'); 
            }
            else {
            
                if (receiver_userid == data.userId || data.from == 'Me') {
                    if ($('#is_active_chat').val() == 'Yes') {
                        displayMessage(data);
                        $('#chat_message').val(''); 
                        $('#emotion').prop('selectedIndex', 0);
                        
                        // SELON CONSIGNES : Si c'est un message reçu, il faut l'annoter pour pouvoir répondre
                        if (data.from != 'Me') {
                            setTimeout(function() {
                                addAnnotationToLastMessage(data.message_id, data.userId);
                            }, 500);
                        }
                    }
                }
            }
        }
    };

    conn.onclose = function(event) {
        console.log('Connection Closed!');
    };

    // MODIFICATION : Afficher message sans annotation visible
    function displayMessage(data) {
        const isSender = data.from == 'Me';
        const alignmentStyle = isSender 
            ? 'display: flex; justify-content: flex-end; margin-bottom: 10px;' 
            : 'display: flex; justify-content: flex-start; margin-bottom: 10px;';
        const bubbleStyle = isSender
            ? 'max-width: 70%; padding: 10px; border-radius: 15px; background-color: #d1e7dd; color: #0f5132; text-align: right; word-wrap: break-word; word-break: break-word; box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);'
            : 'max-width: 70%; padding: 10px; border-radius: 15px; background-color: #f8d7da; color: #842029; text-align: left; word-wrap: break-word; word-break: break-word; box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);';

        // MODIFICATION : Supprimer l'affichage des annotations
        const html_data = `
        <div style="${alignmentStyle}">
            <div style="${bubbleStyle}" data-message-id="${data.message_id || ''}" data-from="${data.from}" data-sender-id="${data.userId || ''}">
                <b>${isSender ? 'Vous' : data.from} :</b> ${data.msg}<br />
                <div style="text-align: right; font-size: 12px; color: #555;">
                    <i>${data.datetime}</i>
                </div>
            </div>
        </div>
        `;

        $('#messages_area').append(html_data);
        $('#messages_area').scrollTop($('#messages_area')[0].scrollHeight);
    }

    // Fonction pour obtenir l'emoji correspondant à l'émotion
    function getEmotionEmoji(emotion) {
        const emotions = {
            'joie': '😄',
            'colère': '😡',
            'dégoût': '🤮',
            'tristesse': '😢',
            'surprise': '😲',
            'peur': '😱'
        };
        return emotions[emotion] || emotion;
    }

    // Fonction pour afficher les notifications
    function showNotification(message) {
        const notification = `<div class="annotation-notification">${message}</div>`;
        $('#annotation_notifications').html(notification).show();
        setTimeout(() => {
            $('#annotation_notifications').fadeOut();
        }, 5000);
    }

    // Fonction pour mettre en surbrillance un message à annoter
    function highlightMessageToAnnotate(messageId) {
        $(`[data-message-id="${messageId}"]`).addClass('message-waiting-annotation');
        showAnnotationButtons(messageId);
    }

    // JEU DE RÔLE : Ajouter l'annotation au dernier message reçu
    function addAnnotationToLastMessage(messageId, senderId) {
        const messageElement = $(`[data-message-id="${messageId}"]`);
        if (messageElement.length > 0 && messageElement.data('from') != 'Me') {
            messageElement.addClass('message-waiting-annotation');
            showAnnotationButtons(messageId, senderId);
            updateSendButtonState(false);
            showNotification('C\'est votre tour ! Vous devez annoter ce message avant de pouvoir envoyer votre réponse.');
        }
    }

    // Fonction pour afficher les boutons d'annotation
    function showAnnotationButtons(messageId, senderId) {
        const messageElement = $(`[data-message-id="${messageId}"]`);
        if (messageElement.find('.annotation-buttons').length === 0) {
            const annotationButtons = `
            <div class="annotation-buttons">
                <button class="emotion-btn" onclick="annotateMessage('${messageId}', 'joie')">😄 Joie</button>
                <button class="emotion-btn" onclick="annotateMessage('${messageId}', 'colère')">😡 Colère</button>
                <button class="emotion-btn" onclick="annotateMessage('${messageId}', 'tristesse')">😢 Tristesse</button>
                <button class="emotion-btn" onclick="annotateMessage('${messageId}', 'surprise')">😲 Surprise</button>
                <button class="emotion-btn" onclick="annotateMessage('${messageId}', 'dégoût')">🤮 Dégoût</button>
                <button class="emotion-btn" onclick="annotateMessage('${messageId}', 'peur')">😱 Peur</button>
            </div>
            `;
            messageElement.append(annotationButtons);
        }
    }

    // SELON CONSIGNES : Annoter un message reçu
    window.annotateMessage = function(messageId, emotion) {
        const data = {
            action: 'annotate_message',
            message_id: messageId,
            emotion: emotion,
            annotator_id: $('#login_user_id').val()
        };
        
        conn.send(JSON.stringify(data));
    };

    // Fonction pour supprimer les boutons d'annotation
    function removeAnnotationButtons() {
        $('.annotation-buttons').remove();
        $('.message-waiting-annotation').removeClass('message-waiting-annotation');
    }

    // Fonction pour vérifier si l'utilisateur peut envoyer un message
    function checkCanSendMessage() {
        if (receiver_userid) {
            $.ajax({
                url: "action.php",
                method: "POST",
                data: {
                    action: 'check_can_send',
                    from_user_id: $('#login_user_id').val(),
                    to_user_id: receiver_userid
                },
                dataType: "JSON",
                success: function(data) {
                    updateSendButtonState(data.can_send);
                    if (!data.can_send) {
                        showNotification(data.reason);
                    }
                }
            });
        }
    }

    // Fonction pour mettre à jour l'état du bouton d'envoi
    function updateSendButtonState(canSend) {
        const sendButton = $('#send');
        const chatForm = $('#chat_form');
        
        if (canSend) {
            sendButton.prop('disabled', false).removeClass('send-button-disabled');
            chatForm.removeClass('annotation-required');
        } else {
            sendButton.prop('disabled', true).addClass('send-button-disabled');
            chatForm.addClass('annotation-required');
        }
    }

    // JEU DE RÔLE : Créer la zone de chat avec instructions claires
    function make_chat_area(username) {
        var html = `
            <div style="border: 1px solid #e0e0e0; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); margin-bottom: 20px;">
                <div style="background-color: #f7f7f7; border-bottom: 1px solid #e0e0e0; padding: 10px 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <b style="font-size: 16px;">Discussion avec : <span style="color: #dc3545;">${username}</span></b>
                        </div>
                        <div>
                            <button type="button" id="close_chat_area" style="font-size: 1.5rem; color: #999; cursor: pointer; background: none; border: none;">
                                <span>&times;</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div id="messages_area" style="height: 400px; overflow-y: auto; padding: 10px; background-color: #fdfdfd; font-family: Arial, sans-serif;">
                    <!-- Les messages seront affichés ici -->
                </div>
            </div>

            <form id="chat_form" method="POST" style="display: flex; align-items: center; margin-top: 10px;">
                <select id="emotion" name="annotation" style="border-radius: 20px; padding: 10px; font-size: 14px; margin-right: 10px; height: 50px;" required>
                    <option value="" disabled selected>Annotation</option>
                    <option value="joie">😄 Joie</option>
                    <option value="colère">😡 Colère</option>
                    <option value="dégoût">🤮 Dégoût</option>
                    <option value="tristesse">😢 Tristesse</option>
                    <option value="surprise">😲 Surprise</option>
                    <option value="peur">😱 Peur</option>
                </select>
                <textarea id="chat_message" name="chat_message" placeholder="Écrivez votre message ici (après avoir choisi votre annotation)" style="border-radius: 20px; resize: none; height: 50px; padding: 10px; font-size: 14px; flex-grow: 1; margin-right: 10px;" required></textarea>
                <button type="submit" name="send" id="send" style="background-color: #007bff; border: none; border-radius: 50%; color: white; padding: 15px; cursor: pointer;">
                    ➤
                </button>
            </form>
        `;

        $('#chat_area').html(html); 
    }

    // Sélection d'un utilisateur pour discuter
    $(document).on('click', '.select_user', function(){
        receiver_userid = $(this).data('userid');   
        var from_user_id = $('#login_user_id').val(); 
        var receiver_username = $('#list_username_' + receiver_userid).text(); 

        $('.select_user.active').removeClass('active'); 
        $(this).addClass('active'); 

        make_chat_area(receiver_username); 
        $('#is_active_chat').val('Yes'); 

        // Vérifier si l'utilisateur peut envoyer des messages
        checkCanSendMessage();

        // Récupérer l'historique de discussion
        $.ajax({
            url: "action.php",
            method: "POST",
            data: {
                action: 'fetch_chat',
                to_user_id: receiver_userid, 
                from_user_id: from_user_id     
            },
            dataType: "JSON",
            success: function(data) {
                if (data.length > 0) { 
                    let html_data = '';

                    for (let count = 0; count < data.length; count++) {
                        const isSender = data[count].from_user_id == from_user_id;
                        const alignmentStyle = isSender 
                        ? 'display: flex; justify-content: flex-end; margin-bottom: 10px;' 
                        : 'display: flex; justify-content: flex-start; margin-bottom: 10px;';
                        const bubbleStyle = isSender
                        ? 'max-width: 70%; padding: 10px; border-radius: 15px; background-color: #d1e7dd; color: #0f5132; text-align: right; word-wrap: break-word; word-break: break-word; box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);'
                        : 'max-width: 70%; padding: 10px; border-radius: 15px; background-color: #f8d7da; color: #842029; text-align: left; word-wrap: break-word; word-break: break-word; box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);';

                        // MODIFICATION : Afficher historique sans annotations visibles
                        const username = isSender ? 'Vous' : data[count].from_username;

                        html_data += `
                            <div style="${alignmentStyle}">
                                <div style="${bubbleStyle}" data-message-id="${data[count].message_id}" data-from="${username}" data-sender-id="${data[count].from_user_id}">
                                    <b>${username} :</b> ${data[count].content}<br />
                                    <div style="text-align: right; font-size: 12px; color: #555;">
                                        <i>${data[count].timestamp}</i>
                                    </div>
                                </div>
                            </div>
                        `;
                    }

                    $('#messages_area').html(html_data); 
                    $('#messages_area').scrollTop($('#messages_area')[0].scrollHeight); 

                    // SELON CONSIGNES : Vérifier s'il y a un message non annoté à traiter
                    for (let count = data.length - 1; count >= 0; count--) {
                        const isSender = data[count].from_user_id == from_user_id;
                        if (!isSender) {
                            // C'est un message reçu, vérifier s'il est annoté
                            $.ajax({
                                url: "action.php",
                                method: "POST",
                                data: {
                                    action: 'check_message_annotation',
                                    message_id: data[count].message_id,
                                    user_id: from_user_id
                                },
                                dataType: "JSON",
                                success: function(result) {
                                    if (!result.is_annotated) {
                                        // Message non annoté trouvé
                                        setTimeout(function() {
                                            highlightMessageToAnnotate(data[count].message_id);
                                            updateSendButtonState(false);
                                            showNotification('Vous devez annoter le dernier message reçu avant de pouvoir envoyer une réponse.');
                                        }, 500);
                                    }
                                }
                            });
                            break; // Vérifier seulement le dernier message reçu
                        }
                    }
                }
            }
        });
    });

    // Fermer la zone de chat
    $(document).on('click', '#close_chat_area', function(){
        $('#chat_area').html(''); 
        $('.select_user.active').removeClass('active'); 
        $('#is_active_chat').val('No'); 
        receiver_userid = ''; 
    });

    // SELON CONSIGNES : Soumission du formulaire de chat avec annotation obligatoire
    $(document).on('submit', '#chat_form', function(event){
        event.preventDefault(); 

        var user_id = parseInt($('#login_user_id').val()); 
        var message = $('#chat_message').val().trim(); 
        var emotion = $('#emotion').val();

        if (message && emotion) {
            var data = { 
                userId: user_id,
                msg: message,
                receiver_userid: receiver_userid,
                emotion: emotion // Annotation de l'expéditeur avec son message
            };
        
            conn.send(JSON.stringify(data)); 
        } else {
            showNotification('Veuillez choisir une annotation et entrer un message avant d\'envoyer.');
        }
    });

    // Déconnexion
    $('#logout').click(function(){
        var user_id = $('#login_user_id').val();

        $.ajax({
            url: "action.php",
            method: "POST",
            data: {
                user_id: user_id,
                action: 'leave'
            },
            success: function(data) {
                var response = JSON.parse(data);
                if (response.status == 1) {
                    conn.close();
                    location = 'index.php'; 
                }
            }
        });
    });
});
</script>
</body>
</html>