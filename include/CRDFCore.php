<?php

/**
 * Fichier principal d'inclusion
 * Toutes les pages demandées sont automatiquement incluse avec ce fichier PHP
 * 
 * CRDF France Website
 * www.crdf.fr
 * 
 * @author Jocelyn Gribet
 * @copyright (c) CRDF France
 */

/**
 * Démarrage d'un session PHP
 */

session_start();

/**
 * Définition du chemin absolu pour le framework CRDF Core
 */

define('ROOT', '/root/send-ident' . '/', true);

/**
 * Inclusion des librairies pour l'exécution du site Web
 */

require_once ROOT . 'include/class.mysql.php';
require_once ROOT . 'include/filter.php';
require_once ROOT . 'include/functions.php';
require_once ROOT . 'include/bayes.php';
require_once ROOT . 'include/html.php';
require_once ROOT . 'include/lib/phpmailer/PHPMailerAutoload.php';

/**
 * Connexion à la base de données avec les paramètres requis
 */

$dbLink = new MysqlConnection('mysql.lan', 'send-ident', 'send-ident', 'PBHQSvbKBT2eUhGe');

/**
 * END
 */

?>
