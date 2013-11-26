<?php
//
// Navigation
//
// Remarque :
// D�finition des rubriques et sous rubrique
// On distingue le cas de l'utilisateur connect� et non connect�
//
// La structure d'une entr�e navigation est la suivante
//
// 'level'     => integer (requis), niveau de l'arborescence 1 = rubrique, 2 = sous rubrique, etc.
// 'libelle'   => string  (requis), libelle de la rubrique (ou sous rubrique, etc.) qui sera affich�
// 'url'       => string  (requis), url vers laquelle pointer
// 'acl'       => string  (requis), acl (access control list, �ventuellement compos�e de plusieurs valeurs s�par�es par des pipe)
// 'class'     => string  (option), class ou image pour illustrer la rubrique
// 'titre'     => string  (option), titre (� afficher dans un attribut title, par exemple)
// 'js'        => string  (option), bout de code javascript (pour un �venement onclick, par exemple)
//

if($session->session_expired == FALSE && (!empty($session_user_id))) {

    //
    // Utilisateur connect�
    //

    $navigation = array(
        array(
            'level'     => 1,
            'libelle'   => STR_RUBRIQUE_DECONNEXION,
            'url'       => $session->url(CFG_PATH_HTTP.'/logout.php'),
            'acl'       => '',
            'class'     => 'icon-off icon-white',
        ),
        array(
            'level'     => 1,
            'libelle'   => '',
            'url'       => '',
            'acl'       => '',
        ),
        array(
            'level'     => 1,
            'libelle'   => STR_RUBRIQUE_ACCUEIL,
            'url'       => $session->url(CFG_PATH_HTTP.'/index.php'),
            'acl'       => '',
            'class'     => 'icon-home icon-white',
        ),
        array(
            'level'     => 1,
            'libelle'   => '',
            'url'       => '',
            'acl'       => '',
        ),
        array(
            'level'     => 1,
            'libelle'   => STR_RUBRIQUE_DROPBOX,
            // 'url'       => $session->url(CFG_PATH_HTTP.'/dropbox/upload.php'),
            'url'       => $session->url(CFG_PATH_HTTP.'/dropbox/formulaire_upload.php'),
            'acl'       => 'admin',
            'class'     => 'icon-upload icon-white',
        ),
    );

} else {

    //
    // Utilisateur non connect�
    //

    $navigation = array(
        array(
            'level'     => 1,
            'libelle'   => STR_RUBRIQUE_CONNEXION,
            'url'       => CFG_PATH_HTTP.'/login.php',
            'acl'       => '',
            'class'     => 'icon-off icon-white',
        ),
    );
}

require dirname(__FILE__).'/../../'.'web/theme/'.$cfg_profil['theme'].'/navigation.php';
?>