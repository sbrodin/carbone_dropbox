<?php

define ('CFG_ENV', 'dev');  // Environnement de dev

switch (CFG_ENV) {
    
    //
    // Environnement de dev
    //
    
    case 'dev':
    
        // Database
        
        define ('CFG_CLASS', 'adodb_carbone');  // Class d'abstraction utilis�e
        define ('CFG_TYPE',  'mysqli');         // Type de sgbd cibl� (mysql, postgres, ...)
        
        define ('CFG_HOST', 'localhost');       // Host
        define ('CFG_USER', 'username');        // User
        define ('CFG_PASS', 'password');        // Pass
        define ('CFG_BASE', 'stanislas_carbone_dropbox');     // Base
        
        // Option
        
        define ('CFG_OPTIMISATION_LEVEL', '0'); // Mode Optimisation (champ de bit) : 1 = fewer HTTP request, 2 = JS at the bottom)
        define ('CFG_DEBUG', TRUE);             // Mode Debug
        
        // Options Dropbox
        
        define ('CFG_EMAIL_DROPBOX', 'stanislas.brodin@globalis-ms.com');   // Adresse email du compte Dropbox utilis�
        define ('CFG_PASS_DROPBOX', 'stanislas');                           // Mot de passe du compte Dropbox
        define ('CFG_DEST_DROPBOX', 'test');                                // Dossier de destination de Dropbox
        define ('CFG_PASS_UPLOAD_DROPBOX', 'toto');                         // Mot de passe pour l'upload versDropbox (demand� lors de l'envoi)
        
        break;
      
    //
    // Environnement de prod
    //
  
    case 'prod':
 
         // Database
        
        define ('CFG_CLASS', 'adodb_carbone');  // Class d'abstraction utilis�e
        define ('CFG_TYPE',  'mysqli');         // Type de sgbd cibl� (mysql, postgres, ...)
        
        define ('CFG_HOST', 'localhost');       // Host
        define ('CFG_USER', 'username');        // User
        define ('CFG_PASS', 'password');        // Pass
        define ('CFG_BASE', 'carbone_v51');     // Base
        
        // Option
        
        define ('CFG_OPTIMISATION_LEVEL', '3'); // Mode Optimisation (champ de bit) : 1 = fewer HTTP request, 2 = JS at the bottom)
        define ('CFG_DEBUG', FALSE);            // Mode Debug
        
        break;
}
    
//
// Environnement par defaut
//    
    
// Tables

define ('CFG_TABLE_PREFIX',     '');                            // Prefix sur les tables (public. pour postgres)
define ('CFG_TABLE_SESSION',    CFG_TABLE_PREFIX.'session');    // Table session
define ('CFG_TABLE_USER',       CFG_TABLE_PREFIX.'user');       // Table user
define ('CFG_TABLE_DATA',       CFG_TABLE_PREFIX.'data');       // Table data

// Session

define ('CFG_SESSION_TABLE', CFG_TABLE_SESSION);                    // Table session
define ('CFG_SESSION_PATH', str_replace(@$_SERVER['DOCUMENT_ROOT'], '', realpath(dirname(__FILE__).'/../..').'/'));  // Exemple /carbone_v46/ (utile uniquement pour le mode cookie)
define ('CFG_SESSION_NAME', 'sid');                                 // Nom de l'identifiant de session utilis�
define ('CFG_SESSION_DELAY', '3600');                               // D�lai session (en s)
define ('CFG_SESSION_TRANS', 'cookie');                             // Mode de transmission : url, cookie
define ('CFG_SESSION_LEVEL', '0');                                  // Mode de s�curit� (champ de bit) : 1 = contr�le HTTP_USER_AGENT, 2 = session volatile, 4 = token de session (ie 0 = rien, 7 = tout, 3 = contr�le HTTP_USER_AGENT + session volatile)

// Divers

define ('CFG_PATH_FILE', realpath(dirname(__FILE__).'/../..'));             // Chemin fichier (� coder en dur si besoin)
define ('CFG_PATH_FILE_WEB', CFG_PATH_FILE.'/web');                         // Chemin fichier web
define ('CFG_PATH_FILE_IMAGE', CFG_PATH_FILE_WEB.'/image');                 // Chemin fichier images
define ('CFG_PATH_FILE_UPLOAD', CFG_PATH_FILE_WEB.'/upload');               // Chemin fichier upload


define ('CFG_PATH_HTTP', 'http://'.@$_SERVER['HTTP_HOST'].str_replace(@$_SERVER['DOCUMENT_ROOT'], '', CFG_PATH_FILE));   // Chemin http (� coder en dur si besoin)
define ('CFG_PATH_HTTP_WEB', CFG_PATH_HTTP.'/web');                         // Chemin http web
define ('CFG_PATH_HTTP_IMAGE', CFG_PATH_HTTP_WEB.'/image');                 // Chemin http images
define ('CFG_PATH_HTTP_UPLOAD', CFG_PATH_HTTP_WEB.'/upload');               // Chemin http upload

define ('CFG_TITRE', 'Carbone');                                            // Titre de l'application
define ('CFG_VERSION', '5.1.007');                                          // Version de l'application
define ('CFG_VERSION_CARBONE', '5.1.007');                                  // Version de Carbone
define ('CFG_DATE', '12/10/2013');                                          // Date de derni�res r�vision de l'application
define ('CFG_EMAIL', 'armel.fauveau@globalis-ms.com');                      // Email g�n�rique de contact

define ('CFG_GLOBALIS_TITRE', 'GLOBALIS media systems');                                        // Globalis Titre
define ('CFG_GLOBALIS_HTTP', 'http://www.globalis-ms.com');                                     // Globalis Url
define ('CFG_GLOBALIS_ALT', 'e-SSII PHP, SSII Web, ing�nierie Web : GLOBALIS media systems');   // Globalis Alt

// Chemin fichier de log

define ('CFG_PATH_FILE_LOG_SQL', '/tmp/carbone.log');       // Chemin fichier log SQL

// Themes

define ('CFG_THEME_DEFAULT', 'bootstrap');                  // Theme par defaut

// Langue

define ('CFG_LANGUE_DEFAULT', 'fr');                        // Langue par defaut

$cfg_langue = array(
        'fr'=>'fr',
        'uk'=>'uk',
        );

// Profil par defaut � adapter en fonction des besoins fonctionels
// Les champs user_id, acl, theme et langue sont requis

$cfg_profil   = array (
        'user_id'=>'',
        'actif'=>'',
        'acl'=>'',
        'poste'=>'',
        'nom'=>'',
        'prenom'=>'',
        'email'=>'',
        'password'=>'',
        'couleur'=>'',
        'theme'=>CFG_THEME_DEFAULT,
        'langue'=>CFG_LANGUE_DEFAULT,
        'last'=>''
        );

// Options

define ('CFG_PRINTABLE',    TRUE);      // Mode Impression
define ('CFG_DOC',          TRUE);      // Mode Documentation

// Si debugeur activ�

if(CFG_DEBUG) {
    // D�clenchement du compteur pour la mesure du temps d'ex�cution du parsing
    list($usec, $sec) = explode(" ", microtime());
    $time_start=((float)$usec + (float)$sec);
    // Inclusion de la lib Debug
    require dirname(__FILE__).'/../lib/lib_debug.php';
}
?>
