<?php
/*
 * Fonction get_menu_acl($menu, $acl)
 * -----
 * Permet de récupérer un tableau de menu, où toutes les entrées non permises via les acl ont été supprimées.
 * -----
 * @param   array       $menu                   tableau de navigation 'maximal'
 * @param   string      $acl                    chaine d'acl avec séparateur '|'
 * -----
 * @return  array       $menu_acl               tableau de navigation limité aux acl
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function get_menu_acl($menu, $acl) {

    $menu_user = array();
    $acl_user=explode('|',$acl);

    foreach($menu as $v) {
        if($v['acl']=='')
            $menu_user[] = $v;
        else {
            $acl_menu=explode('|',$v['acl']);
            $intersection=array_intersect($acl_menu, $acl_user);
            if(!empty($intersection))
                $menu_user[] = $v;
        }
    }

    //print_rh($menu_user);

    return $menu_user;
}

/*
 * Fonction get_menu_global($menu)
 * -----
 * Menu principal
 * Fonction d'affichage du menu global de navigation multidimensionnel
 * On traite ici les éléments de niveau 1 (rubrique) et 2 (sous rubrique)
 * -----
 * @param   array       $navigation             tableau de navigation
 * -----
 * @return  string      $data                   le flux HTML
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function get_menu_global($navigation) {

    $pere=0;
    $fils=0;
    $tmp=array();

    foreach($navigation as $v) {
        //
        // Initialisation des valeurs par non requise
        //
        if(!isset($v['titre']))
            $v['titre'] = $v['libelle'];

        if(!isset($v['class']))
            $v['class'] = '';

        if(!isset($v['js'])) $v['js'] = '';

        //
        // Construction du tableau temporaire
        //

        if($v['level']==1) {
            $fils=0;
            $pere++;
            $tmp[$pere]['libelle']  = $v['libelle'];
            $tmp[$pere]['url']      = $v['url'];
            $tmp[$pere]['titre']    = $v['titre'];
            $tmp[$pere]['class']    = $v['class'];
            $tmp[$pere]['js']       = $v['js'];
        }
        elseif($v['level']==2) {
            $fils++;
            $tmp[$pere]['rubrique'][$fils]['libelle']   = $v['libelle'];
            $tmp[$pere]['rubrique'][$fils]['url']       = $v['url'];
        }
    }

    $whereis='http://'.@$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
    $whereisdir=dirname($whereis);

    $data='';

    if(isset($tmp)) {
        $data.= "<div class=\"navbar-collapse\">\n";
        $data.= "<ul class=\"nav\">\n";
        foreach($tmp as $v) {
            if(!isset($v['rubrique'])) { // rubrique simple
                if($v['url']!='')
                    $data.="<li><a href=\"".$v['url']."\" title=\"".$v['titre']."\"><i class=\"".$v['class']."\"></i> ".$v['libelle']."</a></li>\n";
            }
            else {
                $data.= "<li class=\"dropdown\">\n";
                $data.="<a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\"><i class=\"".$v['class']."\"></i> ".$v['libelle']." <b class=\"caret\"></b></a>\n";
                $data.="<ul class=\"dropdown-menu\">\n";
                foreach($v['rubrique'] as $w) {
                    if($w['url']!='')
                        $data .= "<li><a href=\"".$w['url']."\">".$w['libelle']."</a></li>\n";
                    else {
                        if($w['libelle']!='')
                            $data.="<li class=\"nav-header\">".$w['libelle']."</li>\n";
                        else
                            $data.="<li class=\"divider\"></li>\n";
                    }
                }
                $data.="</ul>\n";
                $data.="</li>\n";
            }
        }
    }

    if($data!='') {
        $data.="</ul>\n";
        $data.="</div><!-- /.nav-collapse -->\n";
    }

    return $data;
}

/*
 * Fonction get_menu_local($menu)
 * -----
 * Onglet secondaire
 * Fonction d'affichage du menu local de navigation multidimensionnel
 * On traite ici les éléments de niveau 3 (rubrique)
 * -----
 * @param   array       $navigation             tableau de navigation
 * -----
 * @return  string      $data                   le flux HTML
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function get_menu_local($navigation, $get) {

    global $cfg_profil;

    $whereis=$_SERVER['PHP_SELF'];

    $pere=0;
    $fils=0;
    $tmp=array();
    $flag=TRUE;

    foreach($navigation as $v) {

        //
        // Initialisation des valeurs par non requise
        //

        if(!isset($v['titre'])) $v['titre'] = '';

        if(isset($v['image']))
            $v['image'] = CFG_PATH_HTTP_WEB.'/theme/jaune/image/'.$v['image'];
        else
            $v['image'] = '';

        if(!isset($v['js'])) $v['js'] = '';

        // Si flag est à TRUE, on regarde s'il y a des rubriques de niveau 3 ou 4

        if($flag) {
            if($v['level']==3) {
                $fils=0;
                $pere++;
                $tmp[$pere]['libelle']  = $v['libelle'];
                $tmp[$pere]['url']      = sprintf($v['url'], '&amp;'.$get);
                $tmp[$pere]['titre']    = $v['titre'];
                $tmp[$pere]['image']    = $v['image'];
                $tmp[$pere]['js']       = $v['js'];
            }
        }
    }

    $data ="";
    if(isset($tmp)) {
        foreach($tmp as $b) {        
            if(strstr($b['url'],$_SERVER['PHP_SELF']) && $b['libelle']!=STR_RETOUR)
                $data .="<li class=\"active\">";
            else
                $data .="<li>";
         
            $data .="<a href=\"".$b['url']."\">".$b['libelle']."</a></li>\n";
        }
    }

    if($data!='')
        $data="<div class=\"onglet\">\n<ul class=\"nav nav-tabs\">\n".$data."</ul>\n</div>\n";

    return $data;
}
?>