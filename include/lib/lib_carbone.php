<?php
/*
 * Fonction print_rh($data)
 * -----
 * Fonctionnalit� d'aide au debug
 * Affichage d'une variable avec une mise en forme HTML
 * -----
 * @param   string      $data                   nom de la variable � afficher
 * -----
 * @return  string                              le contenu de la variable (entre balise <pre> et </pre>)
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function print_rh($data) {
    echo "<pre>\n";
    print_r($data);
    echo "</pre>\n";
}

/*
 * Fonction load_head($css, $js)
 * -----
 * Chargement des script JS
 * -----
 * @param   array       $css                    tableau contenant les feuilles CSS � charger
 * @param   array       $js                     tableau contenant les scripts JS � charger
 * @global  const       LOAD_JAVASCRIPT         constante contenant les scripts JS � charger
 * -----
 * @return  string                              flux HTML de chargement des feuilles CSS et scripts JS
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function load_head($css, $js) {
    global $cfg_profil;

    $head='';

    if(defined('LOAD_JAVASCRIPT') && LOAD_JAVASCRIPT!='') {
        $tmp=explode('|', LOAD_JAVASCRIPT);
        $tmp=array_unique($tmp);
        // On charge les scripts en cherchant �ventuellement les versions minified
        foreach($tmp as $value) {
            $filename_file=substr(CFG_PATH_FILE_WEB."/js/".$value, 0, -3);
            $filename_http=substr(CFG_PATH_HTTP_WEB."/js/".$value, 0, -3);
            if(file_exists($filename_file.".min.js"))
                $js[]="\t<script type=\"text/javascript\" src=\"".$filename_http.".min.js"."\"></script>\n";
            else
                $js[]="\t<script type=\"text/javascript\" src=\"".$filename_http.".js"."\"></script>\n";

            if(strstr($value, 'wysihtml5'))
                //$js[]="\t<script type=\"text/javascript\" src=\"".CFG_PATH_HTTP_WEB."/js/bootstrap_wysihtml5/wysihtml5-0.3.0.min.js\"></script>\n";
                $css[]="\t<link rel=\"stylesheet\" href=\"".CFG_PATH_HTTP_WEB."/theme/".$cfg_profil['theme']."/css/bootstrap-wysihtml5.css\" type=\"text/css\" />\n";
            elseif(strstr($value, 'datepicker'))
                $css[]="\t<link rel=\"stylesheet\" href=\"".CFG_PATH_HTTP_WEB."/theme/".$cfg_profil['theme']."/css/datepicker.css\" type=\"text/css\" />\n";
            elseif(strstr($value, 'autocomplete'))
                $css[]="\t<link rel=\"stylesheet\" href=\"".CFG_PATH_HTTP_WEB."/theme/".$cfg_profil['theme']."/css/autocomplete.css\" type=\"text/css\" />\n";
            elseif(strstr($value, 'notice'))
                $css[]="\t<link rel=\"stylesheet\" href=\"".CFG_PATH_HTTP_WEB."/theme/".$cfg_profil['theme']."/css/notice.css\" type=\"text/css\" />\n";

        }
    }

    // Supression des doublons eventuels et chainage

    $css=implode('', array_unique($css));
    $js=implode('', array_unique($js));

    // On contr�le s'il faut charger jquery

    if(!(strstr($js, 'jquery-1.7.2.min.js')) && strstr($js, 'jquery.'))
        $js="\t<script type=\"text/javascript\" src=\"".CFG_PATH_HTTP_WEB."/js/jquery-1.7.2.min.js\"></script>\n".$js;

    // Mise en cache eventuelle

    if((CFG_OPTIMISATION_LEVEL&1)==1) {
        $head.="\n\t<!--css-->\n";
        $head.=optimize_head($css, $type="css");
        $head.="\t<!--start js-->\n";
        $head.=optimize_head($js, $type="js");
    }
    else {
        $head.="\n\t<!--css-->\n";
        $head.=$css;
        $head.="\t<!--start js-->\n";
        $head.=$js;
    }
    
    $head.="\t<!--stop js-->\n";

    return $head;
}

/*
 * Fonction optimize_head($js)
 * -----
 * Optimisation du <head></head>
 * -----
 * @param   string      $flux                   variable contenant le flux html
 * @param   string      $type                   variable contenant le type (css ou js)
 * -----
 * @return  string                              flux optimis� a mettre en cache
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function optimize_head($flux='', $type) {
    global $cfg_profil;

    $html='';
    $file='';
    preg_match_all('/="'.addcslashes(CFG_PATH_HTTP_WEB, '/').'(.*)"/Uims', $flux, $tmp);

    // on tri la liste pour eviter les doublons et garder des crc conformes

    $list=$tmp[1];
    sort($list);
    $crc32=CFG_VERSION.'.'.sprintf("%u",crc32(implode(':', $list)));
    $filename=CFG_PATH_FILE_WEB.'/cache/'.$crc32.'.'.$type;

    if(!file_exists($filename)) {
        foreach($tmp[1] as $value) {
            $file.="\n\n";
            $file.=file_get_contents(CFG_PATH_FILE_WEB.$value);
        }

        if($type=='css') {
            // Changement des url
            $file = preg_replace("/url(.*)(['\"]\.\.|\.\.)(.*)(['\"]\))(.*)/", "url(..$3)$5", $file);
            $file = preg_replace("/url(.*)(\.\.)(.*)\)(.*)/", "url(../theme/".$cfg_profil['theme']."$3)$4", $file);
            // Suppression des blancs multiples
            $file = preg_replace('# +#', ' ', $file);
            // Suppression des tabulations et des nouvelles lignes
            $file = str_replace(array("\n\r", "\r\n", "\r", "\n", "\t"), '', $file);
            // Suppression des commentaires
            $file = preg_replace('~/\*(?s:.*?)\*/|^\s*//.*~m', '', $file);
            // Traitement des "espace , espace", "espace ; espace", des "espace : espace", des "espace {"
            $file = str_replace(array(' ,',', ',' , '), ',', $file);
            $file = str_replace(array(' ;','; ',' ; '), ';', $file);
            $file = str_replace(array(' :',': ',' : '), ':', $file);
            $file = str_replace(array(' {','{ ',' { '), '{', $file);
            $file = str_replace(array(' }','} ',' } '), '}', $file);
            // Traitement des 0px vers 0
            $file = str_replace(array(': 0px',':0px'), ':0', $file);
            $file = str_replace(' 0px', ' 0', $file);
        }

        file_put_contents($filename, $file);
    }

    if($type=='css') {
        $html.="\t<link rel=\"stylesheet\" href=\"".CFG_PATH_HTTP_WEB."/cache/".$crc32.'.'.$type."\" type=\"text/css\" />\n";
    }
    elseif($type=='js') {
        $html.= "\t<script type=\"text/javascript\" src=\"".CFG_PATH_HTTP_WEB."/cache/".$crc32.'.'.$type."\"></script>\n";
        /*
        $html.= "\t<script type=\"text/javascript\"><!--\n";
        $html.= "\t    var script = document.createElement('script');\n";
        $html.= "\t    script.src = '".CFG_PATH_HTTP_WEB."/cache/".$crc32.'.'.$type."';\n";
        $html.= "\t    script.type = 'text/javascript';\n";
        $html.= "\t    document.getElementsByTagName('head')[0].appendChild(script);\n";
        $html.= "\t// --></script>\n";
        */

    }
    return $html;
}

/*
 * Fonction load_profil($session_user_id)
 * -----
 * Chargement du profil d'un utilisateur d'apr�s son id
 * -----
 * @param   int         $session_user_id        id de l'utilisateur
 * @global  string      $db                     instance de connexion SGBD
 * @global  array       $cfg_profil             tableau associatif du profil
 * -----
 * @return  array                               tableau associatif du profil
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function load_profil($session_user_id) {
    global $db;
    global $cfg_profil;

    $sql = 'SELECT * FROM '.CFG_TABLE_USER.' WHERE user_id='.$db->qstr($session_user_id);

    $recordset = $db->execute($sql);

    if(!$recordset) {
        foreach($cfg_profil as $key => $value)
            $tmp["$key"]=$value;
    }
    else {
        $row = $recordset->fetchrow();
        foreach($cfg_profil as $key => $value)
            $tmp["$key"]=htmlentities($row["$key"], ENT_COMPAT | ENT_HTML5, 'UTF-8');
    }

    return  $tmp;
}

/*
 * Fonction get_user()
 * -----
 * Permet de lister les utilisateurs connect�
 * -----
 * @param   array           $output             le tableau de donn�es de sortie (doit pointer sur des clefs retourn�es par $session-user())
 * @param   boolean         $print              le mode de sortie (TRUE par d�faut, sinon retour du flux)
 * @param   string          $titre              le titre eventuel
 * -----
 * @return  string								le flux html
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function get_user($output, $print=TRUE, $titre='') {
    global $session;

    $tmp=$session->user();

    if(!empty($tmp)) {
        $flux = '';
        $flux.= "<div class=\"well\">\n";

        foreach($tmp as $value) {
            $flux.="<p>";
            foreach($output as $data) {
                $flux.=$value[$data].' ';
            }
            $flux.="</p>\n";
        }
        $flux.= "</div>\n";
        if($titre!='')
            $flux='<h3>'.$titre.'</h3>'.$flux;
        if($print===TRUE)
            echo $flux;
        else
            return $flux;
    }
}

/*
 * Fonction get_theme()
 * -----
 * Permet de charger le tableau des themes disponibles
 * -----
 *
 * -----
 * @return  array								le tableau
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function get_theme() {
    $data=array();
    $dir=scandir(CFG_PATH_FILE_WEB.'/theme/');

    foreach($dir as $v) {
        if($v[0]!='.' && is_dir(CFG_PATH_FILE_WEB.'/theme/'.$v))
            $data[$v] = trim($v);
    }
    return $data;
}

/*
 * Fonction get_file_info($path)
 * -----
 * Permet de r�cupp�rer les infos li�es � un fichier : taille, poids, etc.
 * -----
 * @param   string      $path                   le chemin complet du fichier
 * -----
 * @return  array                               le tableau des informations
 * -----
 * $Author: Carine $
 * $Copyright: GLOBALIS media systems $
 */

function get_file_info($path) {
    if(file_exists($path))
    {
        $size=(int) filesize($path);
        $return['extension']=substr(strrchr($path,'.'), 1);
        $return['size']=(int) ($size/1024);

        // Si c'est une image, on va �galement rechercher sa largeur et sa hauteur
        if (in_array($return['extension'],array('gif','jpg','png','bmp')))
        {
            $dimension=getimagesize($path);
            if(is_array($dimension))
            {
                    $return['width']=(int) $dimension[0];
                    $return['height']=(int) $dimension[1];
            }
            else
            {
                    $return['width']=FALSE;
                    $return['height']=FALSE;
            }
        }
    }
    else
    {
        $return=FALSE;
    }
    return $return;
}
/*
 * Fonction form($data, $edit)
 * -----
 * Construction de la brique form
 * Cette fonction sert � merger les donn�es par d�fauts
 * avec celles �ventuellement en base ou contenues dans $_POST
 * Cette fonction sert �galement � inclure la lib form (par double bond)
 * -----
 * @param   array       $data                   la structure des donn�es par d�faut
 * @param   array       $edit                   la structure des donn�es �ventuellement en base
 * -----
 * @param   array       $data                   la structure des donn�es merg�es
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function form($data, $edit) {
    require_once 'lib_form.php';           // Lib de gestion des formulaires
    require_once 'lib_test.php';      	    // Lib de gestion des tests unitaires

    if(empty($_POST) && !empty($edit))
        $data = array_merge($data, $edit);
    else
        $data = array_merge($data, $_POST);

    return $data;
}

/*
 * Fonction backoffice($structure)
 * -----
 * Construction de la brique backoffice
 * Cette fonction sert �galement � inclure la lib backoffice (par double bond)
 * -----
 * @param   array       $structure              la structure
 * @param   mixed       $db                     instance de connexion SGBD ($db par d�faut)
 * -----
 * @return  mixed                               le flux HTML (string) ou affichage direct (print) [default]
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function backoffice($structure, $db=FALSE) {
    require_once 'lib_backoffice.php';     // Lib de gestion des briques backoffice (CRUD)
    
    global $session;
    if($db==FALSE)
        global $db;

    //
    // Si ['config']['type'] n'existe pas, on l'initialise � print
    //

    if(!isset($structure['config']['type']))
        $structure['config']['type']='print';

    //
    // Si ['config']['ajax'] n'existe pas ou qu'il n'est pas � 'on'
    //

    if (!(isset($_GET['ajax']) && $_GET['ajax']=='on')) {
        if($structure['config']['type']=='string')
            return backoffice_kernel($structure, $db);
        else
            print backoffice_kernel($structure, $db);
    }

    //
    // Sinon
    //

    else {
        if(strstr($_SERVER['REQUEST_URI'], $structure['context']['name'].'_')) {
            // On purge le flux

            ob_clean();

            // Param�trage de l'ent�te

            header('Content-Type: text/html; charset=utf-8');

            // On bascule forc�ment en mode print

            print backoffice_kernel($structure, $db);

            // On ferme tout

            require 'close.php';

            die();
        }
    }
}

/*
 * Fonction get_url($url, $name='', $value='')
 * -----
 * Construction d'une url avec reprise des variables en GET.
 * -----
 * @param   string      $url                    l'URL de base (index.php, etc.)
 * @param   string      $name                   nom eventuel d'une variable pass�e en GET � modifier (optionnel)
 * @param   string      $value                  valeur a affecter � cette variable (optionnel)
 * -----
 * @return  string                              l'URL
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function get_url($url, $name='', $value='') {
    global $session;

    $tmp_get='';
    $flag=FALSE;

    if(!empty($_GET)) {
        reset($_GET);

        // Si CFG_SESSION_TRANS en mode url
        // Et
        // Si CFG_SESSION_LEVEL en mode volatile

        if(isset($_GET[CFG_SESSION_NAME]) && CFG_SESSION_TRANS=='url' && (CFG_SESSION_LEVEL&2)==2) {
            $_GET[CFG_SESSION_NAME]=$session->get_session_id();
        }

        // Suite du traitement

        foreach($_GET as $k=>$v) {
            //
            // Protection xss
            //
            strip_tags($v);
            $v=htmlspecialchars($v, ENT_QUOTES);

            if($k==$name) {
                $flag=TRUE;
                $tmp_get.=$k.'='.$value.'&amp;';
            }
            else
                $tmp_get.=$k.'='.$v.'&amp;';
        }
    }

    if(!$flag && $name!='' && $value!='')
        $tmp_get.=$name.'='.$value.'&amp;';

    if($tmp_get!='')
        $tmp_get=$url.'?'.substr($tmp_get,0,-5);
    else
        $tmp_get=$url;

    return $tmp_get;
}

/*
 * Fonction add_upload($data)
 * -----
 * Ajout d'un fichier par upload (pour le moment, le fichier est dans le repertoire temporaire)
 * A noter qu'en enrichissant cette fonction ou en en cr�ant un nouvelle, il est possible, par exemple, de retailler une image, etc.
 * On peut �galement choisir d'enrichir ou de cr�er une autre fonction de test_upload
 * -----
 * @param   array       $data                   nom de la variable upload
 * -----
 *
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function add_upload($data) {

    // Lors de l'�tape de test (test_upload), la structure $_FILES a �t� enrichie
    // Deux clefs ont �t� ajout�e : rename (fonction de renommage) et path (chemin de stockage final)

    // print_rh($_FILES);

    if(isset($_FILES[$data.'_tmp']['error']) && ($_FILES[$data.'_tmp']['error']==0)) {
        $final_name='';

        // Renommage

        if(function_exists($_FILES[$data.'_tmp']['rename']))
            $final_name=call_user_func($_FILES[$data.'_tmp']['rename'], $data);
        else
            $final_name = strtolower(uniqid('').strrchr($_FILES[$data.'_tmp']['name'], '.'));

        // On supprime eventuellement l'ancien fichier

        if(isset($_POST[$data]) && $_POST[$data]!='')
            unlink($_FILES[$data.'_tmp']['path'].'/'.$_POST[$data]);

        // On d�place le fichier

        move_uploaded_file($_FILES[$data.'_tmp']['tmp_name'], $_FILES[$data.'_tmp']['path'].'/'.$final_name);
        $_POST[$data]=$final_name;
    }
}

/*
 * Fonction del_upload($filename='', $sql='')
 * -----
 * Suppression d'un fichier par upload
 * -----
 * @param   string      $filename               nom du fichier � supprimer (vide par d�faut)
 * @param   string      $sql                    requete � jouer en base (vide par d�faut)
 * @param   string      $path_file              le chemin fichier (par d�faut CFG_PATH_FILE_UPLOAD)
 * @global  string      $db                     instance de connexion SGBD
 * -----
 *
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function del_upload($filename='', $sql='', $path_file=CFG_PATH_FILE_UPLOAD) {

    global $db;

    // Suppression Fichier

    if($filename!='') @unlink ($path_file.'/'.$filename);

    // Suppression Base

    if($sql!='')    $db->execute($sql);
}

/*
 * Fonction redirect($url)
 * -----
 * Effectue une redirection HTTP ou JS vers l'url sp�cifi�e
 * -----
 * @param   string      $url                    URL de redirection
 * @global  string      $session                instance de session
 * -----
 * @return  void
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function redirect($url) {
    global $session;
    global $db;

    // Si pas de header envoy� (par exemple, mode ob), redirection mode php
    if(!headers_sent()) {
        $url=$session->url($url, FALSE);
        $session->close();
        $db->close();

        header('Location: '.$url);
        exit();
    }
    // Sinon, redirection mode JS
    else {
        $url=$session->url($url);
        $session->close();
        $db->close();

        echo '<script type="text/javascript">window.location.href=\''.$url.'\'</script>';
        exit();
    }

    die();
}

/*
 * Fonction date_iso_to($date_iso, $format)
 * -----
 * Convertit une date au format ISO vers un format donn�
 * -----
 * @param   string      $date_iso               la date au format ISO
 * @param   string      $format                 le format de conversion (par d�faut d-m-Y H:i:s)
 * -----
 * @return  string                              la date dans le format donn�e
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function date_iso_to($date_iso, $format="d-m-Y H:i:s") {
    if(preg_match('#(\d{4})-(\d{2})-(\d{2})(?: (\d{2}):(\d{2}):(\d{2}))?#', $date_iso, $match)){
        $timestamp = mktime(@$match[4], @$match[5], @$match[6], $match[2], $match[3], $match[1]);
        return date($format, $timestamp);
    }
}

/*
 * Fonction date_to_iso($date, $format)
 * -----
 * Convertit une date dans un format donn� vers un format ISO
 * -----
 * @param   string      $date                   la date au format donn�
 * @param   string      $format                 le format de la date en entr�e (par d�faut d-m-Y H:i:s)
 * -----
 * @return  string                              la date dans le format donn�e
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function date_to_iso($date, $format="d-m-Y H:i:s") {
    $j = 0;
    for($i = 0; $i < strlen($format); $i++){
        switch($format{$i}){
            case 'Y' :  $date_iso['Y'] = $date{$j++};
                        $date_iso['Y'].= $date{$j++};
                        $date_iso['Y'].= $date{$j++};
                        $date_iso['Y'].= $date{$j++};
                        break;
            case 'm' :  $date_iso['m'] = $date{$j++};
                        $date_iso['m'].= $date{$j++};
                        break;
            case 'd' :  $date_iso['d'] = $date{$j++};
                        $date_iso['d'].= $date{$j++};
                        break;
            case 'H' :  $date_iso['H'] = $date{$j++};
                        $date_iso['H'].= $date{$j++};
                        break;
            case 'i' :  $date_iso['i'] = $date{$j++};
                        $date_iso['i'].= $date{$j++};
                        break;
            case 's' :  $date_iso['s'] = $date{$j++};
                        $date_iso['s'].= $date{$j++};
                        break;
            default  :  $j++;
        }
    }
    if(!isset($date_iso['m'])){
        $date_iso['m'] = 1;
    }
    if(!isset($date_iso['d'])){
        $date_iso['d'] = 1;
    }

    $timestamp = mktime(@$date_iso['H'], @$date_iso['i'], @$date_iso['s'], @$date_iso['m'], @$date_iso['d'], @$date_iso['Y']);
    return date('Y-m-d H:i:s', $timestamp);
}

/*
 * Fonction abstract_string($param)
 * -----
 * Permet de couper proprement une chaine trop longue
 * -----
 * @param   array       $param
 *                                              ['string']  => la chaine dont ont veut obtenir un extrait
 *                                              ['end']     => la chaine qui vient completer l'extrait
 *                                              ['length']  => longueur souhaite de l'extrait
 *                                              ['fixed']   => type de c�sure (si TRUE, l'extrait aura pour longueur length)
 *                                              ['liste']   => liste des caract�res pouvant faire office de c�sure
 * -----
 * @return  string      $string                 la chaine c�sur�e
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function abstract_string($param) {
    // Recuperation des parametres

    extract($param);

    // Test des parametres

    if(!isset($string)) die("Erreur lors du passage de param�tres");
    if(!isset($end)) $end='...';
    if(!isset($liste)) $liste=array(' ',',',';',"\n","\r",'.');
    if(!isset($length)) $length=30;
    if(!isset($fixed)) $flag=FALSE;

    $string=substr($string, 0, $length);

    if(isset($fixed))
        return $string.$end;

    // Correction Armel (22/03/2004)

    if (strlen($string) < $length)
        return $string;
    else
        $length = strlen($string);

    $length--;
    while (!in_array($string[$length],$liste) && $length!==0)
        $length--;

    if($length>0)
        return substr($string, 0, $length+1).$end;
    else
        return $string.$end;
}

/*
 * Fonction delete_accent($chaine)
 * -----
 * Supprime les caract�res accentu�s d'une chaine
 * -----
 * @param   string      $chaine                 la chaine � traiter
 * -----
 * @return  string                              la chaine filtr�e de ses accents
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function delete_accent($chaine) {
    return(utf8_encode(strtr(utf8_decode($chaine),
                 '�����������������������������������������������������',
                 'AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn')));
}

/*
 * Fonction email($to, $subject, $message, $type='text', $header='', $param='', $pj=array())
 * -----
 * Permet d'envoyer un mail
 * En utilisant cette fonction email() plut�t que la fonction native mail()
 * Il sera plus facile de wrapper de nouveaux param�tres, par exemple dans le champ header
 * Afin de contourner les probl�mes de messages de spam (par exemple)
 * -----
 * @param   string      $to                     adresse du destinataire
 * @param   string      $subject                sujet
 * @param   string      $message                corps du message
 * @param   string      $type                   le type :
 *                                              - text : mail au format text (par d�faut)
 *                                              - html : mail au format html
 * @param   string      $header                 header specifique (par defaut, From, Reply-To et X-Mailer seront renseign�s)
 * @param   string      $param                  param�tre(s) optionnel(s)
 * @param   string      $pj                     tableau de pi�ce(s) jointe(s) �ventuelle(s)
 * -----
 * @return  void
 * -----
 * $Author: arnaud $
 * $Copyright: GLOBALIS media systems $
 */
function email($to, $subject, $message, $type='text', $header='', $param='', $pj = array()) {
    // V�rification de l'existence des pi�ces jointes
    if(!empty($pj)){
        $tmp = $pj;
        unset($pj);

        foreach($tmp as $file){
            if(file_exists($file) && is_readable($file)){
                $pj[] = $file;
            }
        }
    }

    /* Ajout de Lionel pour g�rer l'utf8 */

    $charset = 'iso-8859-1';
    // V�rification de l'usage d'utf8
    if(preg_match('%^(?:
        [\x09\x0A\x0D\x20-\x7E] # ASCII
        | [\xC2-\xDF][\x80-\xBF] # non-overlong 2-byte
        | \xE0[\xA0-\xBF][\x80-\xBF] # excluding overlongs
        | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
        | \xED[\x80-\x9F][\x80-\xBF] # excluding surrogates
        | \xF0[\x90-\xBF][\x80-\xBF]{2} # planes 1-3
        | [\xF1-\xF3][\x80-\xBF]{3} # planes 4-15
        | \xF4[\x80-\x8F][\x80-\xBF]{2} # plane 16
        )*$%xs', $subject.$message))
        $charset = 'utf-8';

    // S'il y a des caract�res sp�ciaux dans le sujet, on l'encode
    if(preg_match_all('/[\000-\010\013\014\016-\037\177-\377]/', $subject, $matches))
        $subject = " =?$charset?B?".base64_encode($subject)."?=";

    /* Fin de l'ajout */

    if($header == '') {
        $header = 'From: '.CFG_EMAIL."\r\n";
        $header.= 'Reply-To: '.CFG_EMAIL."\r\n";
        $header.= 'X-Mailer: '.CFG_TITRE.' '.CFG_VERSION.' - PHP ' . phpversion()."\r\n";
    }

    $header.= 'MIME-Version: 1.0'."\r\n";

    if(!empty($pj)){
        $boundary = md5(uniqid(rand(), TRUE));
        $header.= 'Content-Type: multipart/mixed; boundary="'.$boundary.'";'."\r\n";
        //$header.= 'Content-Transfer-Encoding: 7bit'."\r\n";

        $body = '--'.$boundary."\r\n";
        if($type == 'text'){
            $body.= 'Content-Type: text/plain; charset="'.$charset.'"'."\r\n";
        }elseif($type == 'html'){
            $body.= 'Content-Type: text/html'."\r\n";
        }
        $body.= 'Content-Transfer-Encoding: 7bit'."\r\n";
        //$body.= 'Content-Transfer-Encoding: quoted-printable'."\r\n";
        $body.= "\r\n";
        $body.= $message."\r\n";
        $body.= '--'.$boundary."\r\n";
        for($i = 0; $i < count($pj); $i++){
            if(function_exists('mime_content_type')){
                $content_type = mime_content_type($pj[$i]);
            }else{
                $content_type = 'application/octet-stream';
            }
            $body.= 'Content-Type: '.$content_type.'; name='.basename($pj[$i])."\r\n";
            $body.= 'Content-Transfer-Encoding: base64'."\r\n";
            $body.= 'Content-ID: image'.md5(uniqid(rand(), TRUE))."\r\n";
            $body.= "\r\n";
            $body.= chunk_split(base64_encode(file_get_contents($pj[$i])))."\r\n";
            $body.= '--'.$boundary;
            if($i == count($pj) - 1){
                $body.= '--';
            }
            $body.= "\r\n";
        }
    }elseif($type == 'text'){
        $header.= 'Content-Type: text/plain; charset="'.$charset.'"'."\r\n";
        //$header.= 'Content-Transfer-Encoding: 7bit'."\r\n";
        //$header.= 'Content-Transfer-Encoding: quoted-printable'."\r\n";
        $body = $message;
    }elseif($type == 'html'){
        $header.= 'Content-Type: text/html; charset="'.$charset.'"'."\r\n";
        //$header.= 'Content-Transfer-Encoding: 7bit'."\r\n";
        //$header.= 'Content-Transfer-Encoding: quoted-printable'."\r\n";
        $body = $message;
    }
    $header.= 'Content-Transfer-Encoding: 7bit'."\r\n";

    //echo $header;
    //echo $body, "\n\n";

    // Envoi du message
    mail($to, $subject, $body, $header, $param);
}

/*
 * Fonction utf8_encode_mixed($param, $encode_key=FALSE)
 * -----
 * Permet d'encoder un tableau (et eventuellement les cl�s) en UTF8 
 * -----
 * @param   mixed       $param                  la valeur � convertir
 * @param   boolean     $encode_key             si c'est un tableau, les cl�s doivent-elles �tre converties
 * -----
 * @return  mixed       $result                 la valeur convertie
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */
 
function utf8_encode_mixed($param, $encode_key=FALSE) {
   if(is_array($param)) {
        $result = array();
        foreach($param as $k => $v) {                
            $key = ($encode_key)? utf8_encode($k) : $k;
            $result[$key] = utf8_encode_mixed( $v, $encode_key);
        }
    }
    else
    {
        $result = utf8_encode($param);
    }

    return $result;
}

/*
 * Fonction utf8_decode_mixed($param, $decode_key=FALSE)
 * -----
 * Permet de decoder un tableau (et eventuellement les cl�s) en UTF8 
 * -----
 * @param   mixed       $param                  la valeur � convertir
 * @param   boolean     $encode_key             si c'est un tableau, les cl�s doivent-elles �tre converties
 * -----
 * @return  mixed       $result                 la valeur convertie
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */
 
function utf8_decode_mixed($param, $decode_key=FALSE) {
   if(is_array($param)) {
        $result = array();
        foreach($param as $k => $v) {                
            $key = ($decode_key)? utf8_decode($k) : $k;
            $result[$key] = utf8_decode_mixed( $v, $decode_key);
        }
    }
    else
    {
        $result = utf8_decode($param);
    }

    return $result;
}

/*
 * Fonction check_acl()
 * -----
 * Permet de v�rifier l'acc�s � la ressource URL
 * -----
 *
 * -----
 * @return  bool                                TRUE en cas de succ�s, FALSE dans le cas contraire
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function check_acl() {

    global $navigation;
    global $cfg_profil;
    
    $url=parse_url(CFG_PATH_HTTP);
    $script=str_replace($url['path'], '', $_SERVER['SCRIPT_NAME']);
    $url=CFG_PATH_HTTP.$script;

    foreach($navigation as $k => $v) {
        if(strstr($v['url'], $url)) {

            if($v['acl']=='')
                return TRUE;
            else {
                $acl_user=explode('|',$cfg_profil['acl']);
                $acl_rubrique=explode('|',$v['acl']);

                $intersection=array_intersect($acl_rubrique, $acl_user);

                if(empty($intersection))
                    return FALSE;
                else
                    return TRUE;
            }
        }
    }

    return TRUE;
}

/*
 * Fonction check_get($acl, $return=TRUE)
 * -----
 * Permet de v�rifier l'acc�s � la ressource GET
 * -----
 * @param   string       $acl                   $acl de l'utilisateur
 * @param   bool                                TRUE par d�faut, si FALSE retourne le tableau des actions autoris�es
 * -----
 * @return  bool                                TRUE en cas de succ�s, FALSE dans le cas contraire
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function check_get($acl, $return=TRUE) {

    global $navigation;

    $url=parse_url(CFG_PATH_HTTP);
    $script=str_replace($url['path'], '', $_SERVER['SCRIPT_NAME']);
    $url=CFG_PATH_HTTP.$script;

    // Capture de l'entr�e URL dans le tableau de navigation

    foreach($navigation as $k => $v) {
        if(strstr($v['url'], $url)) {
            if(isset($v[$acl]['get'])) {
                $get=$v[$acl]['get'];
                break;
            }
        }
    }

    // Cas ou l'on retourne qu'un booleen 

    if($return==TRUE) {

        // Si pas d'ACL, on retourne TRUE, 
        // Sinon, on construit un tableau avec les donn�es en GET � purger

        if(empty($get))     
            return TRUE;
        else
            $clean=array();

        foreach($get as $a => $b) {
            $b=str_replace(',', '|', $b);
            $tmp=explode('|', $b);
            if(isset($_GET[$a]) && !in_array($_GET[$a], $tmp)) {
                $clean[]=$a;
            }
        }

        // Si le tableau avec les donn�es en GET � purger est vide, on retourne TRUE, 
        // Sinon, on purge toutes les donn�es avant de retourner TRUE

        if(empty($clean))
            return TRUE;
        else {
            foreach($get as $a => $b) {
                unset($_GET[$a]);
            }
            return TRUE;          
        }
    }

    // Cas ou l'on retourne le tableau des actions autoris�es (utile pour la brique BO)

    else {
        $return=array();

        if(empty($get))
            return $return;
        else
            return $get;

    }
}

/*
 * Fonction check_post($acl, $return=TRUE)
 * -----
 * Permet de v�rifier l'acc�s � la ressource POST
 * -----
 * @param   string       $acl                   $acl de l'utilisateur
 * @param   bool                                TRUE par d�faut, si FALSE retourne le tableau des actions autoris�es
 * -----
 * @return  bool                                TRUE en cas de succ�s, FALSE dans le cas contraire
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function check_post($acl, $return=TRUE) {

    global $navigation;

    $url=parse_url(CFG_PATH_HTTP);
    $script=str_replace($url['path'], '', $_SERVER['SCRIPT_NAME']);
    $url=CFG_PATH_HTTP.$script;

    // Capture de l'entr�e URL dans le tableau de navigation

    foreach($navigation as $k => $v) {
        if(strstr($v['url'], $url)) {
            if(isset($v[$acl]['post'])) {
                $post=$v[$acl]['post'];
                break;
            }
        }
    }

    // Cas ou l'on retourne qu'un booleen 

    if($return==TRUE) {

        // Si pas d'ACL, on retourne TRUE, 
        // Sinon, on construit un tableau avec les donn�es en GET � purger

        if(empty($post))     
            return TRUE;
        else
            $clean=array();

        $tmp=explode('|', $post);
        foreach($_POST as $a => $b) {
            if(!in_array($a, $tmp)) {
                $clean[]=$a;
            }
        }

        // Si le tableau avec les donn�es en GET � purger est vide, on retourne TRUE, 
        // Sinon, on purge les donn�es avant de retourner TRUE

        if(empty($clean))
            return TRUE;
        else {
            foreach($clean as $a => $b) {
                unset($_POST[$b]);
            }
            return TRUE;          
        }
    }

    // Cas ou l'on retourne le tableau des actions autoris�es (utile pour la brique BO)

    else {
        $return=array();

        if(empty($post))
            return $return;
        else
            return $post;

    }
}
?>