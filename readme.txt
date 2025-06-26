Pour utiliser l'application, il faut :

1) Installer XAMPP en installant bien Apache et PHPMyAdmin.

2) Installer Ratchet en suivant cette vidéo et installer Composer : https://youtu.be/Bk45OPROcOo?si=cn7VZQ3Lf2IBz0pp  | https://getcomposer.org/download/

3) Placer le dossier du projet dans le dossier "htdocs" de xampp.

4) Créer une base de données sur PHPMyAdmin en y insérant le code SQL situé dans le fichier database_app/database_app.sql.

5) Changer le nom de la base de données dans le fichier database/Database_connection.php en le remplaçant par le nom que vous avez choisis. 
   Changer si nécéssaire les autres informations.

6) Ouvrir deux Shell Windows en se plaçant dans le dossier de l'application.

7) Dans le premier Shell, entrer la commande : php -S localhost:8000

8) Dans le second Shell, entrer la commande : php bin/server.php

10) Ouvrir deux navigateur différents (ou navigation privée) et taper localhost/### en remplaçant ### par le nom du dossier de l'application.


Quand tu change ton mot de passe il faut qu'il soit hacher
Nom utilisateur unique(inscription et changement)