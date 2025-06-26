<?php 

session_start();

if (!isset($_SESSION['user_data']))
{
    header('location:index.php');
}

require('database/UserModel.php');

$user_object = new UserModel;
$login_user_id = '';

foreach ($_SESSION['user_data'] as $key => $value)
{
    $login_user_id = $value['id'];
    $token = $value['token'];
}

$user_object->setUserId($login_user_id);
$user_data = $user_object->get_user_data_by_id();

$message = '';

if (isset($_POST['edit']))
{
    // MODIFICATION : Validation et gestion des erreurs
    if (empty($_POST['username']) || empty($_POST['password_hash'])) {
        $message = '<div class="alert alert-danger" style="color: #dc3545;">Veuillez remplir tous les champs.</div>';
    } elseif (strlen($_POST['password_hash']) < 6) {
        $message = '<div class="alert alert-danger" style="color: #dc3545;">Le mot de passe doit contenir au moins 6 caractères.</div>';
    } elseif ($_POST['username'] !== $user_data['username'] && $user_object->username_exists($_POST['username'])) {
        // MODIFICATION : Vérifier l'unicité du nom d'utilisateur seulement s'il a changé
        $message = '<div class="alert alert-danger" style="color: #dc3545;">Ce nom d\'utilisateur est déjà pris. Veuillez en choisir un autre.</div>';
    } else {
        // MODIFICATION : Vérifier si le mot de passe actuel est correct avant de le changer
        if ($_POST['password_hash'] !== $user_data['password_hash']) {
            // L'utilisateur veut changer son mot de passe (le champ contient un nouveau mot de passe)
            $user_object->setPasswordHash($_POST['password_hash']);
            $password_changed = true;
        } else {
            // Le mot de passe n'a pas changé, ne pas le re-hacher
            $password_changed = false;
        }

        $user_object->setUsername($_POST['username']);
        $user_object->setEmail($_POST['email']);
        $user_object->setUserId($login_user_id);

        // MODIFICATION : Utiliser les nouvelles méthodes spécialisées
        $success = false;
        
        if ($_POST['username'] !== $user_data['username']) {
            // Changer le nom d'utilisateur
            $user_object->setUsername($_POST['username']);
            if ($user_object->update_username()) {
                // Mettre à jour la session
                $_SESSION['user_data'][$login_user_id]['name'] = $_POST['username'];
                $user_data['username'] = $_POST['username'];
                $success = true;
            }
        }
        
        if ($password_changed) {
            // Changer le mot de passe
            if ($user_object->update_password()) {
                $success = true;
            }
        }
        
        // Si aucun changement n'a été fait mais pas d'erreur
        if (!$password_changed && $_POST['username'] === $user_data['username']) {
            $success = true;
        }

        if ($success) {
            $message = '<div class="alert alert-success" style="color: #00ab0a;">Les détails du profil ont été modifiés avec succès.</div>';
        } else {
            $message = '<div class="alert alert-danger" style="color: #dc3545;">Erreur lors de la modification du profil.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Détails du profil</title>

        <link rel="stylesheet" href="style_profile.css">
        <link rel="icon" type="image/x-icon" href="img/bubble-chat.png">
		<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

        <!-- Bootstrap core JavaScript -->
		<script src="vendor-front/jquery/jquery.min.js"></script>
		<script type="text/javascript" src="vendor-front/parsley/dist/parsley.min.js"></script>
		
    </head>
    <body>
        <div class="container">

            <div class="sidebar" id="sidebar">
                <div>
                    <img src="img/profile.png" alt="profil" class="user_icon">
                    <?php echo "<p style='margin-top: 0px; color: white; font-size: 16px; font-weight: 500; text-align: center;'>" . htmlspecialchars($user_data['username']) . '</p>';?>
                </div>
                <ul class="menu">
                    <li class="menu-item">
                        <a href="discussion.php"><img src="img/chat.png" alt="Messages Icon"></a>
                    </li>
                    <li class="menu-item active">
                        <a href="profile.php"><img src="img/settings.png" alt="Settings Icon"></a>
                    </li>
                </ul>
                <div class="logout">
                    <a id="logout"><img src="img/logout.png"/></a>
                </div>
            </div>
                    <input type="hidden" name="login_user_id" id="login_user_id" value="<?php echo $login_user_id; ?>" />
            <div class="main-content" style="font-family:'Poppins';">
                <div class="form-container">
                    <h1>Détails du profil</h1>
                    <?php echo $message; ?>
                    <br/>
                    <form method="POST" id="profile_form" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="name">Nom d'utilisateur</label>
                            <input type="text" name="username" id="username" class="form-control" data-parsley-pattern="/^[a-zA-Z0-9_\s]+$/" data-parsley-minlength="3" required value="<?php echo htmlspecialchars($user_data['username']); ?>" />
                            <small style="color: #666; font-size: 12px;">Le nom d'utilisateur doit être unique sur l'application</small>
                        </div>
                        <div class="form-group">
                            <label for="mail">Adresse Mail</label>
                            <input type="email" name="email" id="email" class="form-control" data-parsley-type="email" required readonly value="<?php echo htmlspecialchars($user_data['email']); ?>" />
                            <small style="color: #666; font-size: 12px;">L'email ne peut pas être modifié</small>
                        </div>
                        <div class="form-group">
                            <label>Nouveau mot de passe</label>
                            <input type="password" name="password_hash" id="password_hash" class="form-control" data-parsley-minlength="6" data-parsley-maxlength="50" placeholder="Saisissez un nouveau mot de passe (minimum 6 caractères)" required />
                        </div>
                        <div class="form-actions">
                            <button style="background-color: #007bff; color: white" type="submit" name="edit" id="save" class="btn btn-succes">Sauvegarder</button>
                            <a href="discussion.php"><button type="button" id="Return">Retour</button></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </body>
    <script type="text/javascript">
		$(document).ready(function(){

            var conn = new WebSocket('ws://localhost:8080?token=<?php echo $token; ?>');

            // MODIFICATION : Validation côté client améliorée
            $('#profile_form').submit(function(e) {
                var username = $('#username').val().trim();
                var password = $('#password_hash').val();

                if (username.length < 3) {
                    e.preventDefault();
                    alert('Le nom d\'utilisateur doit contenir au moins 3 caractères.');
                    return false;
                }

                if (password.length > 0 && password.length < 6) {
                    e.preventDefault();
                    alert('Le mot de passe doit contenir au moins 6 caractères.');
                    return false;
                }

                return true;
            });

            // MODIFICATION : Gérer le champ mot de passe de manière intelligente
            $('#password_hash').on('focus', function() {
                if ($(this).val() === '<?php echo $user_data['password_hash']; ?>') {
                    $(this).val('');
                }
            });

            $('#logout').click(function(){
				user_id = $('#login_user_id').val();

				$.ajax({
					url   :"action.php",
					method:"POST",
					data  : {
						user_id: user_id,
						action : 'leave'
					},
					success:function(data) 
					{
						var response = JSON.parse(data);

						if (response.status == 1) 
						{
							conn.close();
							location = 'index.php';
						}
					}
				})
			});
		})
	</script>
</html>