<?php
//
// Ouvertue de la connexion SGBD
//

$db =ADONewConnection(CFG_TYPE);
$db->connect(CFG_HOST, CFG_USER, CFG_PASS, CFG_BASE);

if(!$db){
    die("Pas de connexion");
}

//
// Passage en UTF-8
//

$db->Execute("SET NAMES 'utf8', character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");

//
// Ouvertue de la session
//

$session = new session;

//
// Si la session a expir�e on va vers la page de login
//

if(!in_array(basename($_SERVER['PHP_SELF']), array('login.php', 'reset.php'))) {
    if(($session->session_expired == TRUE) || empty($session_user_id)) {
        $url=CFG_PATH_HTTP.'/login.php';
        $session->close();
        $db->close();
        header("Location: $url");
        exit();
    }
}

//
// Chargement du fichier de langue
//

require 'langue/langue_'.$cfg_profil['langue'].'.php';

//
// Chargement du fichier de navigation
//

require 'config/navigation.php';


//
// Si la session n'a pas expir�e
//

//
// Securit� : Verification  du forcage �ventuel d'URL
// On regarde si l'intersection (ACL utilisateur / ACL rubrique ou script) n'est pas vide
// En cas de problem, on redirige vers une page d'erreur
//

if($session->session_expired == FALSE) {
    // V�rification de l'acc�s � la ressource URL et � la ressource GET

    if(!check_acl() || !check_get($cfg_profil['acl'])) {
       $url=$session->url(CFG_PATH_HTTP.'/erreur.php', FALSE);
       header("Location: $url");
       exit();
    }
    check_post($cfg_profil['acl']);
}
?>