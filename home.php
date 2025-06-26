<?php

session_start();

if (!isset($_SESSION['user_data']))
{
	header('location:index.php');
}


require('database/UserModel.php');

$login_user_id = '';
$token = '';

foreach ($_SESSION['user_data'] as $key => $value)
{
    $login_user_id = $value['id'];
    $token = $value['token'];
}

$user_object = new UserModel;
$user_object->setUserId($login_user_id);
$user_data = $user_object->get_user_all_data();
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Accueil</title>
        <link rel="stylesheet" href="style_home.css">
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
                    <?php foreach ($user_data as $user) {if ($user['user_token'] === $token) {echo "<p style='margin-top: 0px; color: white; font-size: 16px; font-weight: 500; text-align: center;'>" . $user['username'] . '</p>';}}?>
                </div>
                <ul class="menu">
                    <li class="menu-item active">
                        <a href="home.php"><img src="img/home.png" alt="Home Icon"></a>
                    </li>
                    <li class="menu-item">
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

            <div class="main-content">
                <input type="hidden" name="login_user_id" id="login_user_id" value="<?php echo $login_user_id; ?>" />
                <br/>
                <div class="search-container">
                    <input type="text" class="search-bar" placeholder="Rechercher un utilisateur" name="search" id="searchInput">
                    <img src="img/search.png" alt="Search Icon" class="search-icon">
                </div>

                <h2>Utilisateurs</h2>
                <div class="user-grid" id="userGrid">
                    <?php if (count($user_data) > 1): ?>
                    <?php foreach ($user_data as $user): ?>
                        <?php if ($user['user_token'] !== $token): // Exclure l'utilisateur connecté ?>
                            <div class="user-card">
                                <img src="img/profile.png" alt="<?php echo htmlspecialchars($user['username']); ?>" class="avatar">
                                <p><?php echo htmlspecialchars($user['username']); ?></p>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php else: ?>
                        <p id="noResultsMessage" style="font-size: 16px; color: gray;">Aucun résultat</p>
                    <?php endif; ?>

                </div>
            </div>
        </div>
        <script>
            // Sélectionner les éléments nécessaires
            const searchInput = document.getElementById('searchInput');
            const userGrid = document.getElementById('userGrid');
            const userCards = userGrid.getElementsByClassName('user-card');
            const noResultsMessage = document.getElementById('noResultsMessage'); // Sélectionner le message "Aucun résultat"

            // Ajouter un événement d'écoute à la barre de recherche
            searchInput.addEventListener('input', function (event) {
            const query = event.target.value.toLowerCase();
            let visibleCount = 0; // Compteur pour suivre le nombre d'utilisateurs visibles

            // Parcourir chaque carte utilisateur et appliquer le filtre
            Array.from(userCards).forEach((card) => {
                const userName = card.querySelector('p').textContent.toLowerCase();
                if (userName.includes(query)) {
                card.style.display = 'flex'; // Afficher la carte
                visibleCount++; // Augmenter le compteur si la carte est visible
                } else {
                card.style.display = 'none'; // Masquer la carte
                }
            });

            // Afficher ou masquer le message "Aucun résultat" en fonction du compteur
            if (visibleCount === 0) {
                noResultsMessage.style.display = 'block'; // Afficher le message
            } else {
                noResultsMessage.style.display = 'none'; // Masquer le message
            }
            });

            $(document).ready(function(){

                var conn = new WebSocket('ws://localhost:8080?token=<?php echo $token; ?>');

                // Déconnexion (Lorsque vous cliquez sur le bouton Déconnexion (le bouton est dans ce fichier)) (N.B. Cela met à jour la colonne `is_online` de la table de base de données `Utilisateur` de 'Connexion' à 'Déconnexion')

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
    </body>
</html>