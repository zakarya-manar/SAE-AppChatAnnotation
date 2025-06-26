<?php

session_start();

if (isset($_SESSION['user_data'])) {
    header('location:home.php');
}

$error = '';
$success = '';

if (isset($_POST['register'])) {
    require_once('database/UserModel.php');

    $user_object = new UserModel;

    // MODIFICATION : Validation côté serveur avec vérification unicité nom d'utilisateur
    if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password_hash']) || empty($_POST['confirm_password'])) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif ($_POST['password_hash'] !== $_POST['confirm_password']) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($_POST['password_hash']) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif (strlen($_POST['username']) < 3) {
        $error = 'Le nom d\'utilisateur doit contenir au moins 3 caractères.';
    } else {
        $user_object->setUsername($_POST['username']);
        $user_object->setEmail($_POST['email']);
        $user_object->setPasswordHash($_POST['password_hash']); // Sera haché dans UserModel
        $user_object->setCreatedAt(date('Y-m-d H:i:s'));

        $result = $user_object->save_data();

        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
            header('location:index.php');
            exit();
        } else {
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
        <link rel="stylesheet" href="style_register.css">
        <link rel="icon" type="image/x-icon" href="img/bubble-chat.png">
    </head>
    <body>
        <div class="container_login">
            <img class="image-section" src="img/img_signin.avif" alt="image" height="500" width="400">

            <div class="form-section">
                <h1>Inscription</h1>
                <p>Créez votre compte pour commencer.</p>

                <?php
                if ($error != '') {
                    echo '<div id="danger" style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0;">' . htmlspecialchars($error) . '</div>';
                }
                ?>

                <br/>

                <form id="register-form" method="post" onsubmit="return validateRegisterForm()">
                    <div class="input-group">
                        <label for="username">Nom d'utilisateur</label>
                        <input type="text" name="username" id="username" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    <div class="input-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div class="input-group">
                        <label for="password_hash">Mot de passe</label>
                        <input type="password" name="password_hash" id="password" required>
                    </div>
                    <div class="input-group">
                        <label for="confirm_password">Confirmer le mot de passe</label>
                        <input type="password" name="confirm_password" id="confirm_password" required>
                    </div>
                    <button type="submit" name="register" class="create-account-btn">Créer un compte</button>
                </form>
                <p class="login-link">Vous avez déjà un compte ? <a href="index.php">Connectez-vous</a></p>
            </div>
        </div>

        <script src="script_register.js"></script>
    </body>
</html>