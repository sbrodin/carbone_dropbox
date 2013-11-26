<?php
// Chargement du framework

require 'start_php.php';

// Construction du titre de la page <title>

define('RUBRIQUE_TITRE', STR_DROPBOX_TITRE);

// Embarquement des scripts coté client <script javascript>

define('LOAD_JAVASCRIPT','wysihtml5/wysihtml5-0.3.0.min.js|wysihtml5/bootstrap.wysihtml5.js');

// Debut de l'affichage

require 'start_html.php';

//
// Debut du traitement
//
$passw = CFG_PASS_UPLOAD_DROPBOX; //change this to a password of your choice.


if ($_POST) {
    require 'DropboxUploader.php';


    try {
        // Rename uploaded file to reflect original name
        if ($_FILES['file']['error'] !== UPLOAD_ERR_OK)
            throw new Exception('File was not successfully uploaded from your computer.');

        $tmpDir = uniqid('/tmp/DropboxUploader-');
        if (!mkdir($tmpDir))
            throw new Exception('Cannot create temporary directory!');

        if ($_FILES['file']['name'] === "")
            throw new Exception('File name not supplied by the browser.');

        $tmpFile = $tmpDir.'/'.str_replace("/\0", '_', $_FILES['file']['name']);
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $tmpFile))
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
}
?>
    <div class="box" align="center">
        <br><br><h1>Dropbox Uploader Demo</h1><br><br>

        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="file" /><br><br>
            Password: <input type="password" title="Enter your password" name="txtPassword" /><br/><br/>
            <input type="submit" value="Upload the file to my Dropbox!" />
            <input style="display:none" type="text" name="dest" value="test" /><br/><br/>
        </form>
    </div>

<?php
//
// Fin du traitement
//

require 'stop_php.php';
?>