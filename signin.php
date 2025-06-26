<?php

session_start(); // Démarrage de la session PHP

// Redirection automatique si l'utilisateur est déjà connecté
if (isset($_SESSION['user_data'])) {
    header('location:home.php');
}

// Initialisation des variables d'affichage d'erreur ou de succès
$error = '';
$success = '';

// Traitement du formulaire d'inscription
if (isset($_POST['register'])) {
    require_once('database/UserModel.php'); // Inclusion du modèle utilisateur

    $user_object = new UserModel;

    // --- VALIDATIONS CÔTÉ SERVEUR ---

    // Vérifie que tous les champs sont remplis
    if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password_hash']) || empty($_POST['confirm_password'])) {
        $error = 'Veuillez remplir tous les champs.';

    // Vérifie la correspondance entre les deux mots de passe
    } elseif ($_POST['password_hash'] !== $_POST['confirm_password']) {
        $error = 'Les mots de passe ne correspondent pas.';

    // Longueur minimale du mot de passe
    } elseif (strlen($_POST['password_hash']) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';

    // Longueur minimale du nom d'utilisateur
    } elseif (strlen($_POST['username']) < 3) {
        $error = 'Le nom d\'utilisateur doit contenir au moins 3 caractères.';

    // Si tout est correct, on crée l'utilisateur
    } else {
        $user_object->setUsername($_POST['username']);
        $user_object->setEmail($_POST['email']);
        $user_object->setPasswordHash($_POST['password_hash']); // Le hachage est effectué dans le modèle
        $user_object->setCreatedAt(date('Y-m-d H:i:s'));

        // Appel de la méthode pour enregistrer l'utilisateur en base
        $result = $user_object->save_data();

        // Succès : redirection avec message
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
            header('location:index.php');
            exit();
        } else {
            // Erreur remontée par la base (ex: email déjà utilisé)
            $error = $result['message'];
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Inscription</title>
        <!-- Feuille de style spécifique à la page d'inscription -->
        <link rel="stylesheet" href="style_register.css">
        <link rel="icon" type="image/x-icon" href="img/bubble-chat.png">
    </head>
    <body>
        <div class="container_login">
            <!-- Image décorative à gauche -->
            <img class="image-section" src="img/img_signin.avif" alt="image" height="500" width="400">

            <!-- Section formulaire à droite -->
            <div class="form-section">
                <h1>Inscription</h1>
                <p>Créez votre compte pour commencer.</p>

                <!-- Affichage d'erreurs de validation -->
                <?php
                if ($error != '') {
                    echo '<div id="danger" style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0;">' . htmlspecialchars($error) . '</div>';
                }
                ?>

                <br/>

                <!-- Formulaire d'inscription -->
                <form id="register-form" method="post" onsubmit="return validateRegisterForm()">
                    <!-- Nom d'utilisateur -->
                    <div class="input-group">
                        <label for="username">Nom d'utilisateur</label>
                        <input type="text" name="username" id="username" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>

                    <!-- Email -->
                    <div class="input-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <!-- Mot de passe -->
                    <div class="input-group">
                        <label for="password_hash">Mot de passe</label>
                        <input type="password" name="password_hash" id="password" required>
                    </div>

                    <!-- Confirmation du mot de passe -->
                    <div class="input-group">
                        <label for="confirm_password">Confirmer le mot de passe</label>
                        <input type="password" name="confirm_password" id="confirm_password" required>
                    </div>

                    <!-- Bouton d’envoi -->
                    <button type="submit" name="register" class="create-account-btn">Créer un compte</button>
                </form>

                <!-- Lien vers la connexion -->
                <p class="login-link">Vous avez déjà un compte ? <a href="index.php">Connectez-vous</a></p>
            </div>
        </div>

        <!-- Script JS pour validation côté client -->
        <script src="script_register.js"></script>
    </body>
</html>
