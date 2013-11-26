<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta http-equiv="pragma" content="no-cache">
        <meta http-equiv="expires" content="0">
        <meta http-equiv="Cache-Control" content="no-cache">
        <meta http-equiv="Cache-Control" content="no-store">
        <meta http-equiv="Cache-Control" content="must-revalidate">
        <meta name="description" content="Framework Carbone">
        
        <title><?php echo CFG_TITRE.' V'.CFG_VERSION.'::'.RUBRIQUE_TITRE?></title>
        
        <?php
            echo "<link rel=\"icon\" type=\"image/png\" href=\"".CFG_PATH_HTTP."/favicon.png\" />";

            $css=array();
            $js=array();
           
            // Tableau des feuilles de style CSS
 
            $css[]="\t<link rel=\"stylesheet\" href=\"".CFG_PATH_HTTP_WEB."/theme/".$cfg_profil['theme']."/css/bootstrap.css\" type=\"text/css\" />\n";
            $css[]="\t<link rel=\"stylesheet\" href=\"".CFG_PATH_HTTP_WEB."/theme/".$cfg_profil['theme']."/css/carbone.css\" type=\"text/css\" />\n";
            $css[]="\t<link rel=\"stylesheet\" href=\"".CFG_PATH_HTTP_WEB."/theme/".$cfg_profil['theme']."/css/dropbox_upload.css\" type=\"text/css\" />\n";

            // Tableau des scripts JS
    
            $js[]="\t<script type=\"text/javascript\" src=\"".CFG_PATH_HTTP_WEB."/js/jquery-1.7.2.min.js\"></script>\n";
            $js[]="\t<script type=\"text/javascript\" src=\"".CFG_PATH_HTTP_WEB."/js/bootstrap-2.0.4.min.js\"></script>\n";
            
            // Chainage de l'ensemble et optimisation si nécessaire

            echo load_head($css, $js);
		?>
	</head>
	
    <body>
        <div class="container-fluid"> 
            <div class="row-fluid">
                <div class="span12">   
                    <div class="navbar navbar-fixed-top">
                        <div class="navbar-inner">
                            <div class="container">
                                <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                                    <span class="icon-bar"></span>
                                    <span class="icon-bar"></span>
                                    <span class="icon-bar"></span>
                                </a>
                                <a class="brand" href="#"><?php echo CFG_TITRE.' V'.CFG_VERSION; ?></a>
                                <?php
                                    echo get_menu_global(get_menu_acl($navigation, $cfg_profil['acl'])); 
                                ?>
                            </div>
                        </div><!-- /.navbar-inner -->
                    </div><!-- /.navbar -->
                    
                    <div class="row-fluid">
                        <div class="span10 marge">
                        <h2><?php echo RUBRIQUE_TITRE; ?></h2>