<?php

session_start(); // Démarrage de la session pour gérer les connexions utilisateur

$error = ''; // Initialisation d’un message d’erreur vide

// MODIFICATION 1 : Redirection directe vers la discussion si l'utilisateur est déjà connecté
if (isset($_SESSION['user_data'])) {
    header('location:discussion.php');
    exit();
}

// Traitement du formulaire de connexion
if (isset($_POST['login'])) {
    require_once('database/UserModel.php'); // Inclusion du modèle utilisateur

    $user_object = new UserModel;

    // --- VALIDATION DES CHAMPS ---

    // Si l’un des champs est vide
    if (empty($_POST['email']) || empty($_POST['password_hash'])) {
        $error = 'Veuillez remplir tous les champs.';

    } else {
        // Recherche utilisateur en base via l’email
        $user_object->setEmail($_POST['email']);
        $user_data = $user_object->get_user_data_by_email();

        // Si un utilisateur est trouvé
        if (is_array($user_data) && count($user_data) > 0) {

            // VÉRIFICATION DU MOT DE PASSE AVEC fallback si méthode manquante
            if (method_exists($user_object, 'verifyPassword')) {
                $password_valid = $user_object->verifyPassword($_POST['password_hash'], $user_data['password_hash']);
            } else {
                $password_valid = password_verify($_POST['password_hash'], $user_data['password_hash']);
            }

            if ($password_valid) {
                // Connexion acceptée : mise à jour de l’état en ligne
                $user_object->setUserId($user_data['user_id']);
                $user_object->setIsOnline(True);

                // Génération d’un token de session sécurisé (pour les WebSockets notamment)
                $user_token = bin2hex(random_bytes(16));
                $user_object->setUserToken($user_token);

                // Mise à jour des données en base (token, statut)
                if ($user_object->update_user_login_data()) {
                    $_SESSION['user_data'][$user_data['user_id']] = [
                        'id'      =>  $user_data['user_id'],
                        'name'    =>  $user_data['username'],
                        'token'   =>  $user_token
                    ];

                    // Redirection vers l’interface de tchat
                    header('location:discussion.php');
                    exit();
                }
            } else {
                $error = 'Adresse e-mail ou mot de passe incorrecte !';
            }
        } else {
            $error = 'Adresse e-mail ou mot de passe incorrecte !';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Connexion</title>

        <!-- Feuille de style -->
        <link rel="stylesheet" href="style_register.css">
        <link rel="icon" type="image/x-icon" href="img/bubble-chat.png">
    </head>
    <body>
        <div class="container_login">
            <!-- Image de gauche -->
            <img class="image-section" src="img/img_login.avif" alt="image" height="500" width="400">

            <!-- Section formulaire -->
            <div class="form-section">
                <h1>Connexion</h1>
                <p>Application de chat avec annotations</p>

                <!-- Message de succès après inscription -->
                <?php
                if (isset($_SESSION['success_message'])) {
                    echo '<div id="success" style="color: #00ab0a; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
                    unset($_SESSION['success_message']); // Une fois affiché, on l’efface
                }

                // Affichage des erreurs de connexion
                if ($error != '') {
                    echo '<div id="danger" style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0;">' . htmlspecialchars($error) . '</div>';
                }
                ?>

                <br/>

                <!-- Formulaire de connexion -->
                <form id="login-form" method="post" onsubmit="return validateLoginForm()">
                    <div class="input-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div class="input-group">
                        <label for="password_hash">Mot de passe</label>
                        <input type="password" name="password_hash" id="password" required>
                    </div>
                    <button type="submit" name="login" class="create-account-btn">Se connecter</button>
                </form>

                <!-- Lien vers l’inscription -->
                <p class="login-link">Vous n'avez pas de compte ? <a href="signin.php">Inscrivez-vous</a></p>
            </div>
        </div>

        <!-- Validation JS côté client -->
        <script src="script_register.js"></script>
    </body>
</html>
