<?php
// Chargement du framework

require 'start_php.php';

// Construction du titre de la page <title>

define('RUBRIQUE_TITRE', STR_DROPBOX_TITRE);

// Embarquement des scripts coté client <script javascript>

define('LOAD_JAVASCRIPT','autocomplete/jquery.autocomplete.js|datepicker/bootstrap.datepicker.js|multiselect/jquery.multiselect.js|textarea/jquery.textarea.js');

// Debut de l'affichage

require 'start_html.php';

//
// Debut du traitement
//

// Si Form

// Construction de l'url <form>

$url=get_url(basename($_SERVER['PHP_SELF']));

// Initialisation des valeurs par défaut

$data = array(
    'file' =>        '',
    'txtPassword' => '', 
    'dest' =>        CFG_DEST_DROPBOX, // dossier de destination dans Dropbox
);

// Mise a jour des données selon ce que contient $_POST

$data = form($data, !empty($edit) ? $edit : array());

// Construction du Formulaire

$form_structure = array(
    'champ0' => array(
        'item' => 'form',
        'action' => $url,
        'method' => 'post',
        'legende' => STR_FORM_LEGENDE,
    ),

    'file' => array(
        'item' => 'upload',
        'tpl' => '[(1,20){libelle}(1,60){form}(1,20){legende}]',
        'libelle' => STR_FORMULAIRE_LIBELLE_UPLOAD,
        'value' => $data['file'],
        'path' => CFG_PATH_FILE_UPLOAD,
        'maxsize'=>100*1024*1024,                                                   // Taille max (100 Mo)
        'type'=>array('application/pdf', 'image/jpeg', 'image/png', 'image/gif'),   // Format(s) accepté(s)
        'extension'=>array('pdf', 'jpeg', 'jpg', 'png', 'gif'),                     // Extension(s) acceptée(s)
        'rename'=>'rename_example',                                                 // Renomage (vide par défaut, sinon le nom d'un fonction specifique)
        'test' => array(
            'test_user_function' => 'test_upload',
            'test_error_message' => STR_FORM_E_FATAL_FIELD_SAISIE,
        ),
        'require'=>TRUE,
    ),

    'txtPassword' => array(
        'item' => 'input',
        'tpl' => '[(1,20){libelle}(1,60){form}(1,20){legende}]',
        'libelle' => STR_FORMULAIRE_LIBELLE_INPUT_PASSWORD,
        'value' => $data['txtPassword'],
        'type' => 'password',
        'require' => TRUE,
    ),

    'dest' => array(
        'item' => 'hidden',
        'value' => $data['dest'],
    ),

    'submit' => array(
        'item' => 'button',
        'tpl' => '[(1,20){libelle}(1,60){form}(1,20){legende}]',
        'value' => STR_FORM_SUBMIT,
        'class' => 'btn btn-primary',
        'type' => 'submit',
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
//     'warning' contiendra les erreurs non bloquantes éventuellements issues des tests secondaires

$form_error=form_check($form_structure);

// Si _POST n'est pas vide

if(!empty($_POST)) {

    // Si pas d'erreur bloquante -> Traitement complémentaire

    // if(empty($form_error['fatal'])) {
    //
    // }

    // Si toujours pas d'erreur bloquante -> Traitement sgbd

    if(empty($form_error['fatal'])) {

        // Traitement des modifications

        // if($_GET['action'] == 'edit') {
        //
        // }

        // Traitement des ajouts

        // elseif($_GET['action'] == 'add') {
        //
        // }

        require 'DropboxUploader.php';

        $passw = CFG_PASS_UPLOAD_DROPBOX; //change this to a password of your choice.

        try {
            // Rename uploaded file to reflect original name
            if ($_FILES['file_tmp']['error'] !== UPLOAD_ERR_OK)
                throw new Exception('File was not successfully uploaded from your computer.');

            $tmpDir = uniqid('/tmp/DropboxUploader-');
            if (!mkdir($tmpDir))
                throw new Exception('Cannot create temporary directory!');

            if ($_FILES['file_tmp']['name'] === "")
                throw new Exception('File name not supplied by the browser.');

            $tmpFile = $tmpDir.'/'.str_replace("/\0", '_', $_FILES['file_tmp']['name']);
            if (!move_uploaded_file($_FILES['file_tmp']['tmp_name'], $tmpFile))
                throw new Exception('Cannot rename uploaded file!');
                
            if ($_POST['txtPassword'] != $passw)
                throw new Exception('Wrong Password');

            // Upload
            $uploader = new DropboxUploader(CFG_EMAIL_DROPBOX, CFG_PASS_DROPBOX);// enter dropbox credentials
            $uploader->upload($tmpFile, $_POST['dest']);

            echo '<span style="color: green;font-weight:bold;margin-left:393px;">File successfully uploaded to my Dropbox!</span>';
        } catch(Exception $e) {
            echo '<span style="color: red;font-weight:bold;margin-left:393px;">Error: ' . htmlspecialchars($e->getMessage()) . '</span>';
        }

        // Clean up
        if (isset($tmpFile) && file_exists($tmpFile))
            unlink($tmpFile);

        if (isset($tmpDir) && file_exists($tmpDir))
            rmdir($tmpDir);

        echo form_message(STR_FORM_E_WARNING, $form_error['warning'], 'info', $form_error['jquery']);
        unset($_GET['action']);
    }

    // Si erreur bloquante

    else {
        echo form_message(STR_FORM_E_FATAL, $form_error['fatal'], 'error', $form_error['jquery']);
        $form_view=form_parser($form_structure);
        echo $form_view;
    }
}

// Si _POST est vide

else {
    echo form_parser($form_structure);
}

//
// Fin du traitement
//

require 'stop_php.php';
?>