<?php
// Chargement du framework

require 'start_php.php';

// Construction du titre de la page <title>

define('RUBRIQUE_TITRE', STR_LOGIN_TITRE);

// Embarquement des scripts cot� client <script javascript>

define('LOAD_JAVASCRIPT','');

// Debut de l'affichage

$marge='<p>'.STR_LOGIN_MESSAGE.'</p>';

require 'start_html.php';

//
// Debut du traitement
//

// Construction de l'url <form>

$url=get_url(basename($_SERVER['PHP_SELF']));

// Initialisation des valeurs par d�faut

$data = array(
        'login_email' => '',
        'login_password' => '',
        'perdu_email' => '',
        );

// Mise a jour des donn�es selon ce que contient $_POST

$data = form($data, !empty($edit) ? $edit : array());

// Construction du Formulaire

$form_structure = array(
    'form' => array(
        'item' => 'form',
        'action' => $url,
        'legende' => STR_FORM_LEGENDE,
    ),

    'login_on' => array(
        'item' => 'fieldset',
        'tpl' => '[(3,100){libelle}{form}{legende}]',
        'legende' => STR_LOGIN_FIELDSET_LOGIN,
        'type' => 'on'
    ),

    'login_email' => array(
        'item' => 'input',
        'tpl' => '[(1,20){libelle}(1,40){form}(1,40){legende}]',
        'libelle' => STR_LOGIN_LIBELLE_EMAIL,
        'prepend' => 'icon-envelope',
        'placeholder' => STR_LOGIN_LIBELLE_EMAIL_PLACEHOLDER,
        'accesskey' => 'E',
        'size' => 30,
        'value' => $data['login_email'],
        'test' => array(
            'test_user_function' => 'test_mail',
            'test_error_message' => STR_FORM_E_FATAL_FIELD_SAISIE,
        ),
        'require' => TRUE,
        'js' => 'onfocus="this.className=\'focus\';" onblur="this.className=\'normal\';"',
    ),

    'login_password' => array(
        'item' => 'input',
        'tpl' => '[(1,20){libelle}(1,40){form}(1,40){legende}]',
        'libelle' => STR_LOGIN_LIBELLE_PASSWORD,
        'prepend' => 'icon-lock',
        'placeholder' => STR_LOGIN_LIBELLE_PASSWORD_PLACEHOLDER,
        'accesskey' => 'P',
        'size' => 30,
        'value' => $data['login_password'],
        'type' => 'password',
        'require' => TRUE,
        'js' => 'onfocus="this.className=\'focus\';" onblur="this.className=\'normal\';"',
    ),

    'submit' => array(
        'item' => 'button',
        'tpl' => '[(1,20){libelle}(2,80){form}]',
        'value' => STR_FORM_SUBMIT,
        'type' => 'submit',
        'class' => 'btn btn-primary',
    ),

    'login_off' => array(
        'item' => 'fieldset',
        'type' => 'off'
    ),

    'perdu_on' => array(
        'item' => 'fieldset',
        'tpl' => '[(3,100){libelle}{form}{legende}]',
        'legende' => STR_LOGIN_FIELDSET_PERDU,
        'type' => 'on'
    ),

    'perdu_email' => array(
        'item' => 'input',
        'tpl' => '[(1,20){libelle}(1,40){form}(1,40){legende}]',
        'libelle' => STR_LOGIN_LIBELLE_EMAIL,
        'prepend' => 'icon-envelope',
        'placeholder' => STR_LOGIN_LIBELLE_EMAIL_PLACEHOLDER,
        'accesskey' => 'R',
        'size' => 30,
        'value' => $data['perdu_email'],
        'test' => array(
            'test_user_function' => 'test_mail',
            'test_error_message' => STR_FORM_E_FATAL_FIELD_SAISIE,
        ),
        'require' => TRUE,
        'js' => 'onfocus="this.className=\'focus\';" onblur="this.className=\'normal\';"',
    ),

    'help' => array(
        'item' => 'button',
        'tpl' => '[(1,20){libelle}(2,80){form}]',
        'value' => STR_FORM_LOGIN_HELP,
        'type' => 'submit',
        'class' => 'btn btn-primary',
    ),

    'perdu_off' => array(
        'item' => 'fieldset',
        'type' => 'off'
    ),

);

// form_check retourne un tableau ayant la structure suivante :
//
//     $form_error=array(
//        'fatal' => array(),
//        'warning' => array(),
//     );
//
//     'fatal' contient les erreurs bloquantes
//     'warning' contiendra les erreurs non bloquantes �ventuellements issues des tests secondaires

if(isset($_POST['help'])) {
    $form_structure['login_email']['require']=FALSE;
    unset($form_structure['login_email']['test']);
    $form_structure['login_password']['require']=FALSE;
}
else {
    $form_structure['perdu_email']['require']=FALSE;
    unset($form_structure['perdu_email']['test']);
}

$form_error=form_check($form_structure);

$form_structure['login_email']['require']=TRUE;
$form_structure['login_password']['require']=TRUE;
$form_structure['perdu_email']['require']=TRUE;

// Si _POST n'est pas vide
if(!empty($_POST)) {

    // Si pas d'erreur de saisie -> Traitement compl�mentaire

    if(empty($form_error['fatal'])) {
        if(isset($_POST['help'])) {

            $sql = 'SELECT user_id, password FROM '.CFG_TABLE_USER.' ';
            $sql.= 'WHERE ';
            $sql.= 'actif = '.$db->qstr('1').' ';
            $sql.= 'AND ';
            $sql.= 'email = '.$db->qstr($_POST['perdu_email']);

            $row = $db->getrow($sql);

            if (!$row)
                $form_error['fatal'][]=STR_LOGIN_E_FATAL;
            else {

                $get=uniqid();
                $url=CFG_PATH_HTTP.'/reset.php?password_reset='.$get;

                $sql = 'UPDATE '.CFG_TABLE_USER.' SET ';
                $sql.= 'password_reset = '.$db->qstr($get);
                $sql.= 'WHERE ';
                $sql.= 'email = '.$db->qstr($_POST['perdu_email']);

                $db->execute($sql);

                email($_POST['perdu_email'], STR_LOGIN_EMAIL_SUJECT, sprintf(STR_LOGIN_EMAIL_BODY, CFG_TITRE, $url));
                email($_POST['perdu_email'], STR_LOGIN_EMAIL_SUJECT, sprintf(STR_LOGIN_EMAIL_BODY_HTML, CFG_TITRE, $url), 'html');
                $form_error['warning'][]=STR_LOGIN_E_WARNING;
            }
        }
        elseif(isset($_POST['submit'])) {

            if(preg_match('/^[A-Za-z0-9]+$/', $_POST['login_password'])) {
                $sql = 'SELECT user_id FROM '.CFG_TABLE_USER.' ';
                $sql.= 'WHERE ';
                $sql.= 'email = '.$db->qstr($_POST['login_email']).' ';
                $sql.= 'AND ';
                $sql.= 'password = SHA1('.$db->qstr($_POST['login_password']).') ';
                $sql.= 'AND ';
                $sql.= 'actif = '.$db->qstr('1');

                if ($row=$db->getone($sql)) {
                    $session->register('session_user_id',$row);

                    $cfg_profil=load_profil($session_user_id);
                    $session->register('cfg_profil',$cfg_profil);

                    $sql = 'UPDATE '.CFG_TABLE_USER.' SET ';
                    $sql.= 'last =      '.$db->qstr(date('Y-m-d H:i:s')).' ';
                    $sql.= 'WHERE ';
                    $sql.= 'user_id =   '.$row;

                    $db->execute($sql);

                    $url=$session->url(CFG_PATH_HTTP.'/index.php', FALSE);
                    $session->close();
                    $db->close();
                    header("Location: $url");
                    exit();
                }
                else
                    $form_error['fatal'][]=STR_LOGIN_E_FATAL;
            }
        }
    }
    
    echo form_message(STR_FORM_E_WARNING, $form_error['warning'], 'info', $form_error['jquery']);
    echo form_message(STR_FORM_E_FATAL, $form_error['fatal'], 'error', $form_error['jquery']);

}

echo form_parser($form_structure);

//
// Fin du traitement
//

$session->destroy();
require 'stop_php.php';
?>
