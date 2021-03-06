<?php
/*
 * Fonction backoffice_kernel($structure)
 * -----
 * Construction de la brique backoffice
 * -----
 * @param   array       $structure              la structure
 * @param   mixed       $db                     instance de connexion SGBD
 * @global  string      $session                instance de session
 * @global  string      $session_token          token de session
 * @global  string      $cfg_profil             profil de l'utilisateur courant (pour les couleurs, la gestion du context, etc.)
 * -----
 * @return  mixed                               le flux HTML (string) ou affichage direct (print) [default]
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function backoffice_kernel($structure, $db) {
    global $session;
    global $session_token;
    global $cfg_profil;

    $flux='';

    extract($structure);

    //--------------------------------------------------------------
    // Simplification des actions
    //--------------------------------------------------------------

    if(isset($action)) {
        $acl=check_get($cfg_profil['acl'], FALSE);

        if(!empty($acl) && isset($acl[$script['action']['label']])) {
            $acl_action=str_replace(',', '|', $acl[$script['action']['label']]);
            $acl_action=explode('|', $acl_action);

            foreach($action as $a => $b) {

                if(is_array($b['field'])) {
                    foreach($b['field'] as $c => $d) {
                        if(!in_array($d, $acl_action)) {
                            unset($action[$a]);
                            break;
                        }
                    }            
                }
                else {
                    if(!in_array($b['field'], $acl_action))
                        unset($action[$a]);
                }
            }
            $acl_action=explode('|', $acl[$script['action']['label']]);
        }
        else {
            $acl_action=array();
            foreach($action as $a => $b) {
                if(is_array($b['field'])) {
                    $acl_action[]=implode(',', $b['field']);
                }
                else {
                    $acl_action[]=$b['field'];
                }
            }        
        }
    }

    //print_rh($acl_action);
        
    //--------------------------------------------------------------
    // Gestion du context
    //--------------------------------------------------------------

    $session_context=$session->get_var($context['name']);

    // Initialisation du context si vide

    if(isset($_POST[$context['name'].'_reset']) && $_POST[$context['name'].'_reset']==STR_RESET) {
        $_POST=array();
        unset($session_context);
    }

    if(empty($session_context)) {
        if(isset($navigation))
            $session_context['item']=$navigation['item'];
        else
            $session_context['item']=0;
        $session_context['page']=1;
        $session_context['total']=0;
        $session_context['method']='GET';
        $session_context['where']=array();
        $session_context['like']=array();
        $session_context['logical']=array();
        $session_context['value']=array();
        $session_context['order']='';
        $session_context['requete']='';     // Permet d'�viter de rejouer le COUNT si la requete n'a pas chang�

        $tmp=preg_split('/ORDER BY/i', $requete['select']);
        if(isset($tmp[1]) && strstr($tmp[1], 'ASC'))
            $session_context['type']='ASC';
        else
            $session_context['type']='DESC';
        $session->register($context['name'], $session_context);
    }

    if(!isset($config['logical']) || ($config['logical']!='OR' && $config['logical']!='AND'))
        $config['logical']='';

    // Bug Marie

    if(isset($session_context['page']) && $session_context['page'] == 0)
        $session_context['page'] =1;

    // Traitement des GET
    
    // V�rification de la validit� de la clause "where" (faille d'injection: la clause "where" porte bien sur un champ existant)
    
    if(isset($_GET[$context['name'].'_where'])) {
        $is_valid_where=FALSE;
        foreach($filtre as $value) {
            if($value['field']==$_GET[$context['name'].'_where']) {
                $is_valid_where=TRUE;
                break;
            }
        }
        if($is_valid_where === FALSE)
            unset($_GET[$context['name'].'_where']);
    }

    if(isset($_GET[$context['name'].'_where'])) {

        // Initialisation des clefs where et value

        if(!isset($session_context['where'][$_GET[$context['name'].'_where']])) {
            $session_context['where'][$_GET[$context['name'].'_where']]='';
            $session_context['value'][$_GET[$context['name'].'_where']]=array();
        }

        // Initialisation des clefs like et logical

        foreach($filtre as $value) {
            if($value['field']==$_GET[$context['name'].'_where']) {
                if(isset($value['like']) && $value['like']!='') {
                    // Test compatibilit� ascendante
                    if($value['like']=='TRUE' || $value['like']==='TRUE')
                        $value['like']='%value%';
                    $session_context['like'][$_GET[$context['name'].'_where']]=$value['like'];
                }
                else
                    $session_context['like'][$_GET[$context['name'].'_where']]='';

                if(isset($value['logical']))
                    $session_context['logical'][$_GET[$context['name'].'_where']]=' '.$value['logical'].' ';
                else
                    $session_context['logical'][$_GET[$context['name'].'_where']]='';
            }
            // On en profite pour purger les donn�es inutiles du context, si la clef config['logical'] est vide
            elseif(isset($session_context['where'][$value['field']]) && $config['logical']=='') {
                unset($session_context['where'][$value['field']]);
                unset($session_context['value'][$value['field']]);
                unset($session_context['logical'][$value['field']]);
                unset($session_context['like'][$value['field']]);
            }
        }

        // Cas des champs input et select (type single en hidden)

        if(isset($_GET['single'])) {
            if($_GET[$context['name'].'_value']=='') {
                unset($session_context['where'][$_GET[$context['name'].'_where']]);
                unset($session_context['value'][$_GET[$context['name'].'_where']]);
                unset($session_context['logical'][$_GET[$context['name'].'_where']]);
                unset($session_context['like'][$_GET[$context['name'].'_where']]);
            }
            else {
                unset($session_context['value'][$_GET[$context['name'].'_where']]);
                $session_context['value'][$_GET[$context['name'].'_where']][$_GET[$context['name'].'_value']]=$_GET[$context['name'].'_value'];
            }

        }
        // Cas des autres champs

        else {
            if(in_array($_GET[$context['name'].'_value'], $session_context['value'][$_GET[$context['name'].'_where']])) {
                unset($session_context['value'][$_GET[$context['name'].'_where']][$_GET[$context['name'].'_value']]);
                if(empty($session_context['value'][$_GET[$context['name'].'_where']])) {
                    unset($session_context['where'][$_GET[$context['name'].'_where']]);
                    unset($session_context['value'][$_GET[$context['name'].'_where']]);
                    unset($session_context['logical'][$_GET[$context['name'].'_where']]);
                    unset($session_context['like'][$_GET[$context['name'].'_where']]);
                }
            }
            else {
                if($session_context['logical'][$_GET[$context['name'].'_where']]=='')
                    unset($session_context['value'][$_GET[$context['name'].'_where']]);

                $session_context['value'][$_GET[$context['name'].'_where']][$_GET[$context['name'].'_value']]=$_GET[$context['name'].'_value'];
            }
        }

        $session_context['method']='GET';
    }

    if(isset($_GET[$context['name'].'_order'])) {
    
        // V�rification de la validit� de la clause "order" (faille d'injection: la clause "order" porte bien sur un champ existant et ordonnancable)

        foreach($data as $value) {
            if($value['field']==$_GET[$context['name'].'_order'] && $value['order']==TRUE) {
                $session_context['order']=$_GET[$context['name'].'_order'];
                if($session_context['type']=='DESC')
                    $session_context['type']='ASC';
                else
                    $session_context['type']='DESC';
    
                break;
            }   
        }
    }

    // V�rification de la validit� de la clause "page" (faille d'injection: entier attendu)

    if(isset($_GET[$context['name'].'_page']) && preg_match('/^[0-9]+$/', $_GET[$context['name'].'_page'])) {

        // On limite aussi les risques d'injection d'un nombre de page absurde

        $max_page=(int)($session_context['total']/$session_context['item']);
        if(($max_page*$session_context['item']) != $session_context['total'])
            $max_page++;
        if($max_page==0)
            $max_page=1;

        if($_GET[$context['name'].'_page'] <= $max_page)
            $session_context['page']=$_GET[$context['name'].'_page'];
        else
            $session_context['page']=$max_page;
    }

    // V�rification de la validit� de la clause "item" (faille d'injection: entier attendu et faisant partie du scope de valeurs possibles dans choix_item)

    if(isset($_GET[$context['name'].'_item']) && preg_match('/^[0-9]+$/', $_GET[$context['name'].'_item'])) {
        if(isset($navigation['choix_item']) && !empty($navigation['choix_item']) && in_array($_GET[$context['name'].'_item'],$navigation['choix_item'])) {
            $session_context['item']=$_GET[$context['name'].'_item'];
            $session_context['page']=1;
        }
    }

    //print_rh($session_context);

    $session->register($context['name'], $session_context);

    //--------------------------------------------------------------
    // Zone debug
    //--------------------------------------------------------------
    
    /*
    if(isset($debug)) {
        if(isset($debug['GET']) && $debug['GET']===TRUE) {
            print 'GET';
            print_rh($_GET);
            print '________________________________________<br><br>'."\n";
        }
        if(isset($debug['POST']) && $debug['POST']===TRUE) {
            print 'POST';
            print_rh($_POST);
            print '________________________________________<br><br>'."\n";
        }
        if(isset($debug['SESSION']) && $debug['SESSION']===TRUE) {
            print 'SESSION';
            print_rh($session_context);
            print '________________________________________<br><br>'."\n";
        }
    }
    */
    
    //--------------------------------------------------------------
    // Zone filtre
    //--------------------------------------------------------------

    $flux_filtre='';

    // Affichage de l'aide : Zone filtre

    if(isset($config['help']['outil']) && $config['help']['outil']!='') {
        $tmp='';
        if(isset($config['help']['outil']))
            $tmp=$config['help']['outil'];

        // Si recherche active
        if(isset($recherche) && !empty($recherche) && isset($config['help']['recherche'])) {
            $foo=$recherche;
            $bar=array();
            foreach($foo as $val) {
                $bar[]=$val['label'];
            }
            unset($foo);
            $foo=implode(', ', $bar);
            $tmp.=sprintf($config['help']['recherche'], $foo);
        }

        // Si filtre actif
        if(isset($filtre) && !empty($filtre) && isset($config['help']['filtre'])) {
            $foo=$filtre;
            $bar=array();
            foreach($foo as $val) {
                $bar[]=$val['label'];
            }
            unset($foo);
            $foo=implode(', ', $bar);
            $tmp.=sprintf($config['help']['filtre'], $foo);
            
            $flux_filtre.="<div id=\"".$context['name']."-collapse-filtre\" class=\"collapse bo-help-flux\">\n";
            $flux_filtre.="<table class=\"well\"><tr><td>".$tmp."</td></tr></table>\n";
            $flux_filtre.="</div>\n";
        }        
        
        //$flux_filtre.="<div class='layer_titre'>".STR_BACKOFFICE_HELP_TITRE."</div>\n";
        //$flux_filtre.="<div class='layer_flux'>".$tmp."</div>\n";
    }

    if(!empty($filtre)) {
    
        $flux_filtre.="\n<!-- /.bo-filtre debut -->\n\n";
        $flux_filtre.="<div class=\"bo-filtre\">\n";
        $flux_filtre.="<table class=\"none table-bordered\">\n";
        $flux_filtre.="<tr>\n";
        $flux_filtre.="<th colspan=\"2\" width=\"80%\" align=\"left\">\n";
        $flux_filtre.=STR_BACKOFFICE_FILTRE;        
        $flux_filtre.="</th>\n";
        $flux_filtre.="<th colspan=\"1\" width=\"20%\" align=\"right\">\n";
        $flux_filtre.="<form class=\"bo-filtre form-inline\" action=\"".$session->url($script['name'])."\" method=\"post\" name=\"".$context['name']."_reset\">\n";
        
        if(isset($filtre) && !empty($filtre) && isset($config['help']['filtre']))
            $flux_filtre.="<button type=\"button\" value=\"".STR_BACKOFFICE_HELP_TITRE."\" class=\"btn\" data-toggle=\"collapse\" data-target=\"#".$context['name']."-collapse-filtre\"><i class=\" icon-info-sign\"></i> ".STR_BACKOFFICE_HELP_TITRE."</button>\n";
            
        $flux_filtre.="<button type=\"submit\" value=\"".STR_RESET."\" class=\"btn\" name=\"".$context['name']."_reset\"><i class=\"icon-refresh\"></i> ".STR_RESET."</button>\n";
        $flux_filtre.="</form>\n";
        $flux_filtre.="</th>\n";
        $flux_filtre.="</tr>\n";

        foreach($filtre as $fil) {
            $flux_filtre.="<tr>\n";
            $flux_filtre.="<td colspan=\"1\" width=\"20%\">\n".$fil['label']."\n</td>\n";
            $tmp='';
            $tmp_separator=' ';
            $type=explode(' ', $fil['type']);
            foreach($type as $typ) {
                switch($typ) {
                    case 'num' :
                        for($loop=48;$loop<58;$loop++) {
                            $tmp_url=$script['name'].'?'.$context['name'].'_where='.$fil['field'].'&amp;'.$context['name'].'_value='.chr($loop);

                            if(isset($session_context['where'][$fil['field']]) && in_array(chr($loop), $session_context['value'][$fil['field']]))
                                $tmp.='<a href="'.$session->url($tmp_url).'"><button class="btn btn-mini btn-info" type="button">'.chr($loop).'</button></a>';
                            else
                                $tmp.='<a href="'.$session->url($tmp_url).'">'.chr($loop).'</a>';

                            $tmp.=$tmp_separator;
                        }
                        //$tmp=substr($tmp, 0, -(strlen($tmp_separator)));
                    break;
                    case 'alpha' :
                        for($loop=65;$loop<91;$loop++) {
                            $tmp_url=$script['name'].'?'.$context['name'].'_where='.$fil['field'].'&amp;'.$context['name'].'_value='.chr($loop);

                            if(isset($session_context['where'][$fil['field']]) && in_array(chr($loop), $session_context['value'][$fil['field']]))
                                $tmp.='<a href="'.$session->url($tmp_url).'"><button class="btn btn-mini btn-info" type="button">'.chr($loop).'</button></a>';
                            else
                                $tmp.='<a href="'.$session->url($tmp_url).'">'.chr($loop).'</a>';

                            $tmp.=$tmp_separator;
                        }
                        //$tmp=substr($tmp, 0, -(strlen($tmp_separator)));
                    break;
                    case 'liste' :
                    
                        $tmp.="<form>\n";

                        foreach($fil['value'] as $k => $v) {
                            $tmp_url=$script['name'].'?'.$context['name'].'_where='.$fil['field'].'&amp;'.$context['name'].'_value='.$k;
                            if(isset($session_context['where'][$fil['field']]) && in_array($k, $session_context['value'][$fil['field']]))
                                $tmp.="<a href=\"".$session->url($tmp_url)."\"><button class=\"btn btn-small btn-info\" type=\"button\">".$v."</button></a> \n";
                            else
                                $tmp.="<a href=\"".$session->url($tmp_url)."\"><button class=\"btn btn-small\" type=\"button\">".$v."</button></a> \n";

                            //$tmp.=$tmp_separator;
                        }
                        
                        $tmp.="</form>\n";

                        //$tmp=substr($tmp, 0, -(strlen($tmp_separator)));
                    break;
                    case 'select' :
                        $tmp.= "<form class=\"form-inline\" action=\"".$session->url($script['name'])."\">\n";
                        $tmp.= '<select name="'.$context['name'].'_where" onchange="window.location.href=\''.$session->url($script['name'].'?'.$context['name'].'_where='.$fil['field']).'&amp;single=TRUE&amp;'.$context['name'].'_value=\'+this.options[this.selectedIndex].value;">'."\n";
                                                
                        foreach($fil['value'] as $k => $v) {
                            if(isset($session_context['where'][$fil['field']]) && current($session_context['value'][$fil['field']])==$k)
                                $selected = ' selected="selected"';
                            else
                                $selected = '';

                            $tmp.="<option value=\"".$k."\"".$selected.">".$v."</option>\n";
                        }
                        $tmp.= "</select>\n";
                        $tmp.= "</form>\n";
                    break;
                    case 'input' :
                        $tmp.= "<form class=\"form-inline\" action=\"".$session->url($script['name'])."\">\n";
                        $tmp.= "<input type=\"text\" name=\"".$context['name']."_value\" id=\"".$fil['field']."\" ";
                        $clear=FALSE;
                        if(isset($session_context['where'][$fil['field']])) {
                            $tmp.="value=\"".htmlentities(current($session_context['value'][$fil['field']]), ENT_COMPAT, 'UTF-8')."\" />";
                            $clear=TRUE;
                        }
                        else
                            $tmp.=" />\n";
                        $tmp.= "<input type=\"hidden\" name=\"".$context['name']."_where\" value=\"".$fil['field']."\" />\n";
                        $tmp.= "<input type=\"hidden\" name=\"single\" value=\"TRUE\" />\n";

                        // Patch Bruno 31/12/09
                        // Gestion des param�tres GET du script name
                        // Patch Lionel 10/01/13
                        // V�rification qu'il y a bien des param�tres en GET

                        if(FALSE !== strpos($script['name'], '?')) {
                            $get = explode('&amp;', preg_replace('/.*\?/U','',$session->url($script['name'])));

                            if(!empty($get)) {
                                foreach($get as $v) {
                                    $clef = explode('=',$v);
                                    if(empty($clef[1])) {
                                        $clef[1] = '';
                                    }
                                    $tmp .= "<input type=\"hidden\" name=\"".$clef[0]."\" value=\"".$clef[1]."\" /> \n";
                                }
                            }
                        }
                        // Fin patch Bruno

                        $tmp.= "<button type=\"submit\" class=\"btn btn-small\">".STR_OK."</button> \n";
                        if($clear)
                            $tmp.= "<button type=\"submit\" class=\"btn btn-small btn-danger\" onclick=\"this.form.".$context['name']."_value.value=''\">".STR_DELETE."</button>\n";
                        $tmp.= "</form>\n";
                    break;
                }
            }
            $flux_filtre.="<td colspan=\"2\" width=\"80%\">\n".$tmp."</td>\n";
            $flux_filtre.="</tr>\n";
        }

        $flux_filtre.="</table>\n</div>\n\n<!-- /.bo-filtre fin -->\n\n";
    }

    //--------------------------------------------------------------
    // Zone data
    //--------------------------------------------------------------

    $flux_total='';
    $flux_outside='';
    $flux_data='';

    // Construction du flux 'action'

    if(!isset($action))
        $action=array();

    $action_tmp=array();
    foreach($action as $act) {
        if(isset($act['type']) && ($act['type']=='local' || $act['type']=='group'))
            if(is_array($act['field']))
                $action_tmp[implode(',',$act['field'])]=1;
            else
                $action_tmp[$act['field']]=1;
    }

    // Construction du flux 'data' + 'action'

    $sql=$requete['select'];

    if($session_context['where']!='' && $session_context['value']!='') {
        $tmp_order=preg_split('/ ORDER BY /i', $sql);
        $tmp_group=preg_split('/ GROUP BY /i', $tmp_order[0]);
        $tmp_where=preg_split('/ WHERE /i', $tmp_group[0]);
        $tmp_where_complement='';

        foreach($session_context['where'] as $i => $j) {
            $tmp='';
            if($session_context['like'][$i]=='') {
                foreach($session_context['value'][$i] as $l) {
                        $tmp.=$i.' = '.$db->qstr($l).$session_context['logical'][$i];
                }
            }
            else {
                foreach($session_context['value'][$i] as $l) {
                    $tmp.=$i.' LIKE '.$db->qstr(str_replace('value', $l, $session_context['like'][$i])).$session_context['logical'][$i];
                }
            }

            if($session_context['logical'][$i]!='')
                $tmp=substr($tmp, 0, -(strlen($session_context['logical'][$i])));
            $tmp_where_complement.='('.$tmp.') '.$config['logical'].' ';
        }

        $tmp_where_complement=substr($tmp_where_complement, 0, -(strlen($config['logical'])+1));

        if($tmp_where_complement!='') {
            if(isset($tmp_where[1]))
                $sql=$tmp_where[0].' WHERE '.$tmp_where[1].' AND ('.$tmp_where_complement.')';
            else
                $sql=$tmp_where[0].' WHERE '.$tmp_where_complement;
        }
        else {
            if(isset($tmp_where[1]))
                $sql=$tmp_where[0].' WHERE '.$tmp_where[1];
            else
                $sql=$tmp_where[0];
        }

        if(isset($tmp_group[1]))
            $sql.=' GROUP BY '.$tmp_group[1];
        if(isset($tmp_order[1]))
            $sql.=' ORDER BY '.$tmp_order[1];

    }

    // Requete sans clause LIMIT ni ORDER : D�termination du nombre d'element total

    if($session_context['order']!='') {
        $tmp_order=preg_split('/ ORDER BY /i', $sql);
        $sql=$tmp_order[0].' ORDER BY '.$session_context['order'].' '.$session_context['type'];
    }

    // Appel de la call_user_function

    if(isset($requete['select_user_function']) && $requete['select_user_function']!=FALSE)
        call_user_func_array($requete['select_user_function'], array(&$sql));
        
    //--------------------------------------------------------------
    // Zone debug
    //--------------------------------------------------------------

    /*
    if(isset($debug)) {
        if(isset($debug['SQL']) && $debug['SQL']===TRUE) {
            print "SQL";
            $foo=$sql;
            $foo=str_replace('FROM',    "\nFROM", $foo);
            $foo=str_replace('WHERE',   "\nWHERE", $foo);
            $foo=str_replace('GROUP',   "\nGROUP", $foo);
            $foo=str_replace('ORDER',   "\nORDER", $foo);

            print "<pre>".$foo."</pre>";
            print "________________________________________<br><br>\n";
        }

        if(isset($debug['SESSION']) && $debug['SESSION']==TRUE) {
            print "SESSION";
            print_rh($session_context);
            print "________________________________________<br><br>\n";
        }
    }
    */
    
    // Requete avec clause LIMIT : R�cupp�ration des donn�es

    if(isset($navigation)) {

        // Nouvelle m�thode
        if($sql!='SELECT FALSE') { // Cas particulier si 'select'=>'SELECT FALSE' (exemple outils/kill.php ou outils/sql.php)
            $sql_tmp=preg_split('/ ORDER BY /i', $sql);
            //La requ�te ci dessous ne marche pas en cas de jointure sur $script['id']['value']
            //$sql_tmp='SELECT COUNT('.$script['id']['value'].') '.stristr($sql_tmp[0], 'FROM');
            if(strstr($sql_tmp[0], 'DISTINCT')) {
                $sql_tmp=$sql_tmp[0];
                $recordset = $db->execute($sql_tmp);
                $session_context['total']=$recordset->recordcount();
                unset($recordset);
            }
            elseif(strstr($sql_tmp[0], ' GROUP BY ')) {
                $sql_tmp='SELECT * '.stristr($sql_tmp[0], 'FROM');
                $recordset = $db->execute($sql_tmp);
                $session_context['total']=$recordset->recordcount();
                unset($recordset);
            }
            else {
                $sql_tmp='SELECT COUNT(*) '.stristr($sql_tmp[0], 'FROM');
                $session_context['total']=$db->getone($sql_tmp);
            }
        }
        else
            $session_context['total']=0;

        // Ancienne m�thode
        //$recordset = $db->execute($sql);
        //$session_context['total']=$recordset->recordcount();

        $session_context['requete']=$sql;

        if($session_context['page']>1) {
            for($session_context['page']; $session_context['page'] >= 1; $session_context['page']--) {
                $sql_numrow = $session_context['item'];
                $sql_offset = ($session_context['page']-1)*($session_context['item']);
                $recordset = $db->selectlimit($sql, $sql_numrow, $sql_offset);
                if($recordset->recordcount()>=1)
                    break;
            }
        }
        else {
            if($session_context['item']!=0) {
                $sql_numrow = $session_context['item'];
                $sql_offset = ($session_context['page']-1)*($session_context['item']);
                $recordset = $db->selectlimit($sql, $sql_numrow, $sql_offset);
            }
            else
                $recordset = $db->execute($sql);
        }

        $session->register($context['name'], $session_context);
        //$sql.=" LIMIT ".(($session_context['page']-1)*($session_context['item'])).', '.$session_context['item'];
    }
    else {
        $sql_numrow = $session_context['item'];
        $sql_offset = ($session_context['page']-1)*($session_context['item']);
        //$recordset = $db->selectlimit($sql, $sql_numrow, $sql_offset);
        $recordset = $db->execute($sql);

        $session_context['total']=$recordset->recordcount();
    }

    // Affichage du nombre total d'�l�ment

    if(isset($config['total']) && $config['total']===TRUE) {
        $tmp='';
        if(isset($config['total_string']))
            $tmp.=$config['total_string'].' '.$session_context['total'];
        else
            $tmp.=STR_FORM_AFFICHAGE_TOTAL.' '.$session_context['total'];
        $flux_total="<!-- /.bo-total debut -->\n\n<div class=\"bo-total\">".$tmp."</div>\n\n<!-- /.bo-total fin -->\n\n";
    }

    //
    // Optimisation de la gestion des images
    //

    foreach($action as $key_param => $param) {
        if(isset($param['format']) && $param['format']=='image' && isset($param['src']) && $param['src']!='') {
            if(is_array($param['src'])) {
                foreach($param['src'] as $key_src => $src) {
                    if(is_file(CFG_PATH_FILE_WEB.'/theme/'.$cfg_profil['theme'].'/image/'.$src))
                        $action[$key_param]['src'][$key_src]=CFG_PATH_HTTP_WEB.'/theme/'.$cfg_profil['theme'].'/image/'.$src;
                    elseif(is_file(CFG_PATH_FILE_IMAGE.'/'.$src))
                        $action[$key_param]['src'][$key_src]=CFG_PATH_HTTP_IMAGE.'/'.$src;
                    else
                        unset($action[$key_param]['src']);
                }
            }
            else {
                if(is_file(CFG_PATH_FILE_WEB.'/theme/'.$cfg_profil['theme'].'/image/'.$param['src']))
                    $action[$key_param]['src']=CFG_PATH_HTTP_WEB.'/theme/'.$cfg_profil['theme'].'/image/'.$param['src'];
                elseif(is_file(CFG_PATH_FILE_IMAGE.'/'.$param['src']))
                    $action[$key_param]['src']=CFG_PATH_HTTP_IMAGE.'/'.$param['src'];
                else
                    unset($action[$key_param]['src']);
            }
        }
    }

    //
    // Affichage des 'action outside'
    //

    $action_active=array();
    $tmp='';
    foreach($action as $act) {
        if(isset($act['type']) && $act['type']=='outside') {
            if(isset($act['script']) && $act['script']!='')
                $tmp_url=$session->url($act['script'].'?'.$script['action']['label'].'='.$act['field']);
            else
                $tmp_url=$session->url($script['name'].'?'.$script['action']['label'].'='.$act['field']);

            if(isset($act['format']) && $act['format']=='button')
                $tmp.='<a href="'.$tmp_url.'"><button type="button" class="btn" value="'.$act['label'].'" onclick=\'location.href="'.$tmp_url.'"\'>'.$act['label'].'</button></a>&nbsp;';
            elseif(isset($act['format']) && $act['format']=='image' && isset($act['src']) && $act['src']!='')
                $tmp.='<a href="'.$tmp_url.'"><img src="'.$act['src'].'" alt="'.$act['label'].'" title="'.$act['label'].'" /></a>';
            elseif(isset($act['format']) && $act['format']=='icon' && isset($act['src']) && $act['src']!='')
                $tmp.='<a href="'.$tmp_url.'" class="btn"><li class="'.$act['src'].'"></li> '.$act['label'].'</a>';
            else
                $tmp.='<a href="'.$tmp_url.'">'.$act['label'].'</a>&nbsp;';
                
            $tmp.="\n";
        }
    }

    if($tmp!='')
        $flux_outside.="<!-- /.bo-outside debut -->\n\n<div class=\"bo-outside\">\n".$tmp."</div>\n\n<!-- /.bo-outside fin -->\n\n";
        //$flux_outside.="<p>".$tmp."</p>\n";

    $indice=0;
    $value=array();

    while ($row = $recordset->fetchrow()) {
        $value[$indice]=array_merge($row, $action_tmp);
        $indice++;
    }

    // Appel de la call_user_function

    if(isset($requete['result_user_function']) && $requete['result_user_function']!=FALSE)
        // call_user_func($requete['select_user_function'], &$value, &$action);
        call_user_func_array($requete['result_user_function'], array(&$value, &$action));

    //--------------------------------------------------------------
    // Simplification des actions
    //--------------------------------------------------------------

    if(!empty($acl) && isset($acl[$script['id']['label']])) {
        $acl_id=explode('|', $acl[$script['id']['label']]);
        foreach($value as $a => $b) {
            if(isset($b[$script['id']['value']]) && !in_array($b[$script['id']['value']], $acl_id)) {
                foreach($acl_action as $c => $d) {
                    if(isset($b[$d]))
                        $value[$a][$d]=0;
                }
            }
        }
    }

    //--------------------------------------------------------------
    // Zone export
    //--------------------------------------------------------------
    
    if(isset($_GET['format']) && isset($export['format']) && in_array($_GET['format'],$export['format']) && isset($_GET['context']) && $_GET['context']==$context['name']) {
        export($_GET['format'], $value, $data, 'eeeeee');
        die();
    }

    //--------------------------------------------------------------
    // Controle sur l'affichage ou non des donn�es vide
    //--------------------------------------------------------------

    $flux_navig_export='';

    if(!empty($value) || (isset($config['view_empty']) && $config['view_empty']===TRUE) || (!isset($config['view_empty']))) {

        // Affichage de l'aide

        if(isset($config['help']['data']) && $config['help']['data']!='') {

            $tmp='';
            if(isset($config['help']['data']))
                $tmp=$config['help']['data'];

            // Si data active
            if(isset($data) && isset($config['help']['order'])) {
                $foo=$data;
                $bar=array();
                foreach($foo as $val) {
                    if($val['order']==TRUE)
                        $bar[]=$val['label'];
                }
                unset($foo);
                if(!empty($bar)) {
                    $foo=implode(', ', $bar);
                    $tmp.=sprintf($config['help']['order'], $foo);
                }
            }

            // Si action active
            if(isset($action)) {
                $foo=$action;
                $bar=array();
                foreach($foo as $val) {
                    if(($val['type']=='global' || $val['type']=='local') && isset($config['help']['action'])) {
                        if(is_array($val['label']))
                            $val['label']=implode(' '.strtolower(STR_OR).' ', $val['label']);
                        $tmp.=sprintf($config['help']['action'], $val['label'], mb_strtolower($val['label'], "UTF-8"));
                    }
                }
            }

            // Si export actif
            if(isset($navigation['choix_item']) && !empty($navigation['choix_item']) && isset($config['help']['navigation'])) {
                $bar=array();
                foreach($navigation['choix_item'] as $val) {
                    if($val==0)
                        $val=STR_ALL;
                    $bar[]=$val;
                }
                $foo=implode(' - ', $bar);
                $tmp.=sprintf($config['help']['navigation'], $navigation['item'], $foo);
            }

            // Si export actif
            if(isset($export['format']) && !empty($export['format']) && isset($config['help']['export'])) {
                $foo=$export['format'];
                $bar=array();
                foreach($foo as $val) {
                    $bar[]=$val;
                }
                unset($foo);
                $foo=implode(', ', $bar);
                $tmp.=sprintf($config['help']['export'], $foo);
            }

            //$flux_data.="<div class='layer_titre'>".STR_BACKOFFICE_HELP_TITRE."</div>\n";
            //$flux_data.="<div class='layer_flux'>".$tmp."</div>\n";
            
            $flux_data.="<div id=\"".$context['name']."-collapse-data\" class=\"collapse bo-help-flux\">\n";
            $flux_data.="<table class=\"well\"><tr><td>".$tmp."</td></tr></table>\n";
            $flux_data.="</div>\n";
        }

        //--------------------------------------------------------------
        // Zone put_data
        //--------------------------------------------------------------

        $flux_data.="<!-- /.data debut -->\n\n";
        $flux_data.="<div id=\"".$context['name']."_data\" class=\"bo-data\">\n";
        $flux_data.="<div id=\"".$context['name']."_waiting\" class=\"waiting\"></div>\n";

        // Construction du haut du tableau

        // Calcul de la taille 'data'
        // On incr�mente ici afin de compter en plus la colonne action

        $data_width=sizeof($data);
        $data_width++;

        //
        // Affichage des 'action group'
        //

        $action_group=FALSE;
        foreach($action as $act) {
            if(isset($act['type']) && $act['type']=='group') {
                $action_group=TRUE;
                break;
            }
        }

        if($action_group)
            $flux_data.="<form action=\"".$session->url($script['name'])."\" method=\"post\" id=\"".$context['name']."_group\" name=\"".$context['name']."_group\">\n";

        // Construction de la zone TH

        $flux_data.="<table class=\"table table-bordered\">\n";
        $flux_data.="<thead>\n";

        //
        // Gestion de tri de colonne
        //

        $flux_data.="<tr>\n";

        //
        // Patch Julien pour selectionner toutes les cases
        //

        if($action_group)
            $flux_data.="<th width=\"10\"><input type=\"checkbox\" /></th>\n";
            
        if($session_context['type']=='DESC')
            $tmp_order='<span class="label label-info"><i class="icon-circle-arrow-down icon-white"></i></span>';
        else
            $tmp_order='<span class="label label-info"><i class="icon-circle-arrow-up icon-white"></i></span>';

        foreach($data as $dat) {
            $tmp='';
            //print $session_context['order'];
            //print $session_context['type'];
            if($dat['order']==TRUE) {
                $tmp.='<a href="';
                $tmp.=$session->url($script['name'].'?'.$context['name'].'_order='.$dat['field']);
                if($dat['field']==$session_context['order'])
                    $tmp.='"><span class="label label-info">'.$dat['label'].'</span> '.$tmp_order.'</a>';
                else
                    $tmp.='"><span class="label label-info">'.$dat['label']."</span></a>";
            }
            else
                $tmp.=$dat['label'];

            if(isset($dat['width']))
                $flux_data.="<th style=\"width:".$dat['width']."\">".$tmp."</th>\n";
            else
                $flux_data.="<th>".$tmp."</th>\n";
        }

        // Et on ajoute la cellule 'action'

        if($action) {

            // On v�rifie qu'il y a des actions global ou local
            
            $action_locale=false;
            foreach($action as $act) {
                if(isset($act['type']) && ($act['type']=='global' || $act['type']== 'local')) {
                    $action_locale=true;
                    break;
                }
            }

            if($action_locale) {

                //
                // Affichage des 'action global'
                //
    
                $action_active=array();
                foreach($action as $act) {
                    $tmp='';

                    if(isset($act['type']) && $act['type']=='global') {
    
                        if(isset($act['script']) && $act['script']!='')
                            $tmp_url=$session->url($act['script'].'?'.$script['action']['label'].'='.$act['field']);
                        else
                            $tmp_url=$session->url($script['name'].'?'.$script['action']['label'].'='.$act['field']);
    
                        if(isset($act['format']) && $act['format']=='button')
                            $tmp.='<a href="'.$tmp_url.'"><button type="button" class="btn" value="'.$act['label'].'" onclick=\'location.href="'.$tmp_url.'"\'>'.$act['label'].'</button></a>';
                        elseif(isset($act['format']) && $act['format']=='image' && isset($act['src']) && $act['src']!='')
                            $tmp.='<a href=\''.$tmp_url.'\'><img src="'.$act['src'].'" alt="'.$act['label'].'" title="'.$act['label'].'" /></a>';
                        elseif(isset($act['format']) && $act['format']=='icon' && isset($act['src']) && $act['src']!='')
                            $tmp.='<a href="'.$tmp_url.'" class="btn"><li class="'.$act['src'].'"></li> '.$act['label'].'</a>';
                        else
                            $tmp.='<a href="'.$tmp_url.'">'.$act['label'].'</a>';
                    }
                    if($tmp!='')
                        $action_active[]=$tmp;
                }
    
                //print_rh($action);
    
                foreach($action as $val){
                    if($val['type'] == 'global'){
                        $action_global[] = $val;
                    }
                }
    
                //print_rh($action_global);
                //reset($action_active);
    
                $action_nb=sizeof($action_active);
    
                if(isset($config['action']['hide']) && $config['action']['hide']==TRUE) {
                    if(isset($_COOKIE['carbone_cookie_backoffice']) && strstr($_COOKIE['carbone_cookie_backoffice'], $context['name'].'|'))
                        $flux_data.="<th align=\"center\" style=\"display:none;\"";
                    else
                        $flux_data.="<th align=\"center\"";
                }
                else
                    $flux_data.="<th align=\"center\"";
    
                if(isset($config['action']['width']))
                    $flux_data.=" style=\"width:".$config['action']['width']."\">\n";
                else
                    $flux_data.=">\n";

                $flux_data.="<div class=\"bo-global\">\n";    
                $flux_data.="<table>\n";
                $flux_data.="<tr>\n";
    
                if($action_nb==0)
                    $flux_data.="<th>&nbsp;</th>\n";
                else {
                    foreach($action_active as $val) {
                        $flux_data.="<th>".$val."</th>\n";
                    }
                }
    
                // on ajoute l'aide eventuel
                if(isset($config['help']['data']) && $config['help']['data']!='')
                    $flux_data.="<th><button type=\"button\" value=\"".STR_BACKOFFICE_HELP_TITRE."\" class=\"btn\" data-toggle=\"collapse\" data-target=\"#".$context['name']."-collapse-data\"><i class=\" icon-info-sign\"></i> ".STR_BACKOFFICE_HELP_TITRE."</button></th>\n";
                            
                if(isset($config['action']['hide']) && $config['action']['hide']==TRUE)
                    $flux_data.="<th class=\"bo-hide-on\"><button class=\"btn\"><i class=\"icon-resize-small\"></i></button></th>\n";
    
                $flux_data.="</tr>\n";
                $flux_data.="</table>\n";
                $flux_data.="</div>\n";
    
                $flux_data.="</th>\n";
    
                if(isset($config['action']['hide']) && $config['action']['hide']==TRUE) {
                    if(isset($_COOKIE['carbone_cookie_backoffice']) && strstr($_COOKIE['carbone_cookie_backoffice'], $context['name'].'|'))
                        $flux_data.="<th class=\"bo-hide-off\" style=\"width:10px;\" rowspan=\"".(count($value)+1)."\"><button class=\"btn\"><i class=\"icon-resize-full\"></i></button></th>\n";
                    else
                        $flux_data.="<th class=\"bo-hide-off\" style=\"width:10px; display:none;\" rowspan=\"".(count($value)+1)."\"><button class=\"btn\"><i class=\"icon-resize-small\"></i></button></th>\n";
                }
                else
                    $flux_data.="<th class=\"bo-hide-off\" style=\"width:10px; display:none;\" rowspan=\"".(count($value)+1)."\"><button class=\"btn\"><i class=\"icon-resize-full\"></i></button></th>\n";
            }
    
        }
    
        $flux_data.="</tr>\n";
        $flux_data.="</thead>\n";

        // Simplification eventuel du flux (cr�ation de rowspan)

        $rowspan=FALSE;
        $nb_element=count($value);  // Nombre de ligne dans le tableau de r�sultat
        $nb_data=count($data);      // Nombre de colonne dans le tableau de r�sultat

        // On commence par v�rifier qu'au moins une clef rowspan est � TRUE

        for($i=0; $i<$nb_data; $i++) {
            if(isset($data[$i]['rowspan']) && $data[$i]['rowspan']===TRUE) {
                $rowspan=TRUE;
                break;
            }
        }

        // Si c'est le cas, on effectue le traitement pour calculer les rowspan

        if($rowspan===TRUE && !empty($value)) {

            $foo=array();

            // On construit un nouveau tableau avec les donn�es qui seront affich�es et les rowspan

            for($i=0; $i<$nb_data; $i++) {

                $actuel=$value[0][$data[$i]['field']];
                $rang=0;
                $occurence=1;

                $foo[$rang][$data[$i]['field']]=$actuel;
                $foo[$rang][$data[$i]['field'].'_rowspan']=$occurence;

                for($j=1; $j<$nb_element; $j++) {
                    if($value[$j][$data[$i]['field']]==$actuel && (isset($data[$i]['rowspan']) && $data[$i]['rowspan']===TRUE)) {
                        $occurence++;
                    }
                    else {
                        $actuel=$value[$j][$data[$i]['field']];
                        $occurence=1;
                        $rang=$j;
                    }

                    $foo[$rang][$data[$i]['field']]=$actuel;
                    $foo[$rang][$data[$i]['field'].'_rowspan']=$occurence;

                }
            }

            ksort($foo);
        }

        // Construction de la zone TD

        $action_legende_max=array();

        foreach($value as $key => $val) {

            $flux_data.="<tr data-".$script['id']['label']."=\"".$val[$script['id']['value']]."\">";

            //
            // Affichage des 'action group' (suite)
            //

            if($action_group) {

                $action_group_check_box=0;
                foreach($action as $act) {
                    if(isset($act['type']) && $act['type']=='group') {
                        $tmp='';
                        if($val[$act['field']]=='1') {
                            $action_group_check_box++;
                        }
                    }
                }

                if($action_group && $action_group_check_box!=0) {
                    $flux_data.="\n<td width=\"10\" align=\"center\">";
                    $flux_data.="<input type=\"checkbox\" name=\"action_group[]\" value=\"".$val[$script['id']['value']]."\" />";
                    $flux_data.="</td>\n";
                }
                else {
                    $flux_data.="\n<td width=\"10\" align=\"center\">";
                    $flux_data.='&nbsp;';
                    $flux_data.="</td>\n";
                }
            }

            // Affichages des 'data'

            foreach($data as $dat) {
                if($rowspan===TRUE) {   // Si rowspan
                    if(isset($foo[$key][$dat['field']])) {
                        if($dat['field'][0]!='_')
                            $cellule_value=htmlspecialchars(strip_tags($foo[$key][$dat['field']]), ENT_QUOTES);
                        else
                            $cellule_value=$foo[$key][$dat['field']];
                        $cellule_rowspan=$foo[$key][$dat['field'].'_rowspan'];
                        if($cellule_rowspan!=1)
                             $flux_data.="<td data-field=\"".$dat['field']."\" rowspan=\"".$cellule_rowspan."\">";
                        else
                             $flux_data.="<td data-field=\"".$dat['field']."\">";
                        if($cellule_value!='')
                            $flux_data.=$cellule_value;
                        else
                            $flux_data.="&nbsp;";

                        $flux_data.="</td>\n";
                    }
                }
                else {  // Si pas de rowspan
                    if($val[$dat['field']]!='') {
                        if($dat['field'][0]!='_')
                            $flux_data.="<td data-field=\"".$dat['field']."\">".htmlspecialchars(strip_tags($val[$dat['field']]), ENT_QUOTES)."</td>\n";
                        else
                            $flux_data.="<td data-field=\"".$dat['field']."\">".$val[$dat['field']]."</td>\n";
                    }
                    else
                        $flux_data.="<td>&nbsp;</td>\n";
                }
            }

            //
            // Affichage des 'action local'
            //

            if($action && $action_locale) {

                $action_active=array();
                $action_legende=array();

                foreach($action as $act) {
                    if(isset($act['type']) && $act['type']=='local') {
                        $tmp='';

                        if(is_array($act['label']))
                            $act['label_legende']=implode('/', $act['label']);
                        else
                            $act['label_legende']=$act['label']; 

                        if(is_array($act['field']))
                            $field_implode=implode(',', $act['field']);
                        else
                            $field_implode=$act['field'];

                        if($val[$field_implode]>='1') {

                            // Cas d'une structure action multiple
                            if(strstr($field_implode, ',')) {
                                $act['label']=$act['label'][($val[$field_implode]-1)];
                                $act['field']=$act['field'][($val[$field_implode]-1)];

                                if(isset($act['format']) && $act['format']=='image' && isset($act['src']) && $act['src']!='')
                                    $act['src']=$act['src'][($val[$field_implode]-1)];
                                
                                if(isset($act['format']) && $act['format']=='icon' && isset($act['src']) && $act['src']!='')
                                    $act['src']=$act['src'][($val[$field_implode]-1)];

                                if (isset($act['js']) && is_array($act['js']))
                                    $act['js']=$act['js'][($val[$field_implode]-1)];

                                if (isset($act['script']) && is_array($act['script']))
                                    $act['script']=$act['script'][($val[$field_implode]-1)];
                            }

                            if(isset($act['script']) && $act['script']!='')
                                $tmp_url=$session->url($act['script'].'?'.$script['action']['label'].'='.$act['field']);
                            else
                                $tmp_url=$session->url($script['name'].'?'.$script['action']['label'].'='.$act['field']);

                            /*
                            if(isset($act['script']) && $act['script']!='')
                                $tmp.="<a href='".$session->url($act['script'].'?'.$script['action']['label'].'='.$act['field']);
                            else
                                $tmp.="<a href='".$session->url($script['name'].'?'.$script['action']['label'].'='.$act['field']);
                            */
                            
                            $tmp.="<a href=\"".$tmp_url."&amp;".$script['id']['label']."=".$val[$script['id']['value']]."\"";

                            // Ajout de la class

                            if(isset($act['format']) && $act['format']=='button')
                                $tmp.=" class=\"".$act['field'].'_'.$act['type']." btn btn-mini btn-info\"";
                            else
                                $tmp.=" class=\"".$act['field'].'_'.$act['type']."\"";

                            // Ajout du JS si n�cessaire
                            if(isset($act['js']) && $act['js']!='') {
                                $tmp.=' ';
                                if(isset($act['on'])) {
                                    if(!is_array($act['on']))
                                        $tmp.=sprintf($act['js'], $b[$act['on']]);
                                    else {
                                        foreach($act['on'] as $on) {
                                            $act['js']=preg_replace('/%s/', addslashes(htmlentities($val[$on])), $act['js'], 1);
                                        }
                                        $tmp.=$act['js'];
                                    }
                                }
                                else
                                    $tmp.=$act['js'];
                            }
                            if(isset($act['target']) && $act['target']!='')
                                $tmp.='target='.$act['target'];

                            if(isset($act['format']) && $act['format']=='button')
                                $tmp.='><span title="'.$act['label'].'">'.$act['label']."</span></a>";
                            //    $tmp.='><button type="button" class="btn btn-mini btn-info" value="'.$act['label'].'">'.$act['label']."</button></a>";
                            elseif(isset($act['format']) && $act['format']=='image' && isset($act['src']) && $act['src']!='')
                                $tmp.='><img src="'.$act['src'].'" alt="'.$act['label'].'" title="'.$act['label'].'" /></a>';
                            elseif(isset($act['format']) && $act['format']=='icon' && isset($act['src']) && $act['src']!='')
                                $tmp.='><li class="'.$act['src'].'" alt="'.$act['label'].'" title="'.$act['label'].'"></li></a>';
                            else
                                $tmp.='>'.$act['label'].'</a>';

                        }
                        if($tmp!='') {
                            $action_active[]=$tmp;
                            $action_legende[]=$act['label_legende'];
                        }
                        elseif($config['action']['empty']==TRUE) {
                            $action_active[]='&nbsp;';
                            $action_legende[]='&nbsp;';
                        }
                    }
                }

                //print_rh($action_active);
                //print_rh($action_legende);
                //print_rh($action_legende_max);
                //reset($action_active);

                if(sizeof($action_legende) > sizeof($action_legende_max)) {
                    $action_legende_max=$action_legende;
                    $flux_legende='';
                }

                $action_nb=sizeof($action_active);

                if(isset($config['action']['empty']) && $config['action']['empty']==TRUE && $action_nb>0)
                    $action_local_width=' style="width:'.(int)(100/$action_nb).'%;"';
                else if(isset($config['action']['empty']) && $action_nb>0)
                    $action_local_width=' style="width:'.(int)(100/$action_nb).'%;"';
                else
                    $action_local_width='';

                if(isset($config['action']['hide']) && $config['action']['hide']==TRUE) {
                    if(isset($_COOKIE['carbone_cookie_backoffice']) && strstr($_COOKIE['carbone_cookie_backoffice'], $context['name'].'|'))
                        $flux_data.="<td class=\"bo-local\" style=\"display:none;\">\n";
                    else
                        $flux_data.="<td class=\"bo-local\">\n";
                }
                else
                    $flux_data.="<td class=\"bo-local\">\n";

                $flux_data.="<table>\n";
                $flux_data.="<tr>\n";
                if($action_nb==0)
                    $flux_data.="<td".$action_local_width.">&nbsp;</td>\n";
                else {
                    foreach($action_active as $act) {
                        $flux_data.="<td". $action_local_width.">\n".$act."\n</td>\n";
                    }
                }
                $flux_data.="</tr>\n";
                $flux_data.="</table>\n";
                $flux_data.="</td>\n";

                if(isset($config['action']['legende']) && $config['action']['legende']==TRUE && $flux_legende=='') {
                    $flux_legende.="<table>\n";
                    $flux_legende.="<tr>\n";
                    if($action_nb==0)
                        $flux_legende.="<td".$action_local_width.">&nbsp;</td>\n";
                    else {
                        foreach($action_legende_max as $act) {
                            $flux_legende.="<td". $action_local_width.">\n".$act."\n</td>\n";
                        }
                    }
                    $flux_legende.="</tr>\n";
                    $flux_legende.="</table>\n";
                }
            }

            $flux_data.="</tr>\n";
        }

        $flux_data.="</table>\n";

        if($action_group) {
            $flux_data.="\n<div class=\"bo-group\">\n";

            $flux_data.=" <i class=\"icon-repeat\"></i>\n";
            //if (is_file(CFG_PATH_FILE_WEB.'/theme/'.$cfg_profil['theme'].'/image/backoffice/group.png'))
            //    $flux_data.="<img src=\"".CFG_PATH_HTTP_WEB.'/theme/'.$cfg_profil['theme']."/image/backoffice/group.png\" align=\"middle\" alt=\"\" title=\"\" />\n";
            //else
            //    $flux_data.="<img src=\"".CFG_PATH_HTTP_IMAGE."/backoffice/group.png\" align=\"middle\" alt=\"\" title=\"\" />\n";

            //print_rh($action);
            foreach($action as $act) {
                if(isset($act['type']) && $act['type']=='group') {

                    $tmp_action ='';
                    if(isset($act['js']) && $act['js']!='')
                        $tmp_action .= $act['js'];
                    /*                    
                    if(isset($act['script']) && $act['script']!='')
                        $tmp_url=$session->url($act['script'].'?'.$script['action']['label'].'='.$act['field']).'" class="'.$act['field'].'" '.$tmp_action;
                    else
                        $tmp_url=$session->url($script['name'].'?'.$script['action']['label'].'='.$act['field']).'" class="'.$act['field'].'" '.$tmp_action;
                    */
                        
                    if(isset($act['script']) && $act['script']!='') 
                        $tmp_url=$session->url($act['script'].'?'.$script['action']['label'].'='.$act['field']).'" class="'.$act['field'].' '.$tmp_action; 
                    else 
                        $tmp_url=$session->url($script['name'].'?'.$script['action']['label'].'='.$act['field']).'" class="'.$act['field'].' '.$tmp_action; 

                    if(isset($act['format']) && $act['format']=='button')
                        $flux_data.='<a href="'.$tmp_url.'"><button type="button" class="btn btn-mini" value="'.$act['label'].'" onclick=\'location.href="'.$tmp_url.'"\'>'.$act['label']."</button></a>\n";
                    elseif(isset($act['format']) && $act['format']=='image' && isset($act['src']) && $act['src']!='')
                        $flux_data.='<a href="'.$tmp_url.'"><img src="'.$act['src'].'" alt="'.$act['label'].'" title="'.$act['label']."\" /></a>\n";
                    elseif(isset($act['format']) && $act['format']=='icon' && isset($act['src']) && $act['src']!='')
                        $flux_data.='<a href="'.$tmp_url.'" class="btn"><li class="'.$act['src'].'"></li> '.$act['label']."</a>\n";
                    else
                        $flux_data.='<a href="'.$tmp_url.'">'.$act['label']."</a>\n";

                    /*

                    if(isset($act['script']) && $act['script']!='')
                        $flux_data.='<a href="'.$session->url($d['script'].'?'.$script['action']['label'].'='.$act['field']).'" class="'.$act['field'].'" '.$tmp_action.'>';
                    else
                        $flux_data.='<a href="'.$session->url($script['name'].'?'.$script['action']['label'].'='.$act['field']).'" class="'.$act['field'].'" '.$tmp_action.'>';
                        
                    if(isset($act['format']) && $act['format']=='button')
                        $flux_data.='<button type="button" class="btn btn-mini">';

                    $flux_data.=$act['label']."</a>\n";
                    */
                }
            }
            $flux_data.="</div>\n\n";
            $flux_data.="</form>\n";
        }
        
        $flux_data.="</div>\n\n";
        $flux_data.="<!-- /.data fin -->\n\n";

        //--------------------------------------------------------------
        // Zone navigation & export
        //--------------------------------------------------------------

        if(isset($navigation) || isset($export)) {

            //--------------------------------------------------------------
            // Construction de la zone de navigation multipage
            //--------------------------------------------------------------

            $navigation_template_left   ="<!-- /.bo-navig-export debut -->\n\n<div class=\"bo-navig-export\">\n<table>\n<tr>\n<td class=\"bo-navig-export-item\">\n%s</td>\n";
            $navigation_template_center ="<td class=\"bo-navig-export-page\">\n%s</td>\n";
            $navigation_template_right  ="<td class=\"bo-navig-export-export\">\n%s</td>\n</tr>\n</table>\n</div>\n\n<!-- /.bo-navig-export fin -->\n\n";

            if(isset($navigation)) {
                if($session_context['item']!='0')
                    $navig=navig_string($session_context['page'], $session_context['total'], $session_context['item'], $navigation['page'], $session->url($script['name']));
                else
                    $navig='';

                if(isset($navigation['choix_item']) && !empty($navigation['choix_item'])) {
                    $item='';
                    foreach($navigation['choix_item'] as $value) {
                        if($value==0) {
                            if($session_context['item']=='0')
                                $item.='<span class="badge badge-info">'.STR_ALL."</span> \n";
                            else
                                $item.='<a href="'.$session->url($script['name'].'?item=0').'">'.STR_ALL."</a> \n";
                        }
                        else {
                            if($session_context['item']==$value)
                                $item.='<span class="badge badge-info">'.$value."</span> \n";
                            else
                                $item.='<a href="'.$session->url($script['name'].'?item='.$value).'">'.$value."</a> \n";
                        }
                    }
                    $item=str_replace('item=', $context['name'].'_item=', substr($item,0, -2));
                    $flux_navig_export.=sprintf($navigation_template_left, STR_FORM_AFFICHAGE_PAR.' '.$item."\n");
                }
                else
                    $flux_navig_export.=sprintf($navigation_template_left, '&nbsp;');

                $navig=str_replace('page=', $context['name'].'_page=', $navig);
                $flux_navig_export.=sprintf($navigation_template_center, $navig);
            }
            else {
                $flux_navig_export.=sprintf($navigation_template_left, '&nbsp;');
                $flux_navig_export.=sprintf($navigation_template_center, '&nbsp;');
            }

            //--------------------------------------------------------------
            // Construction de la zone export
            //--------------------------------------------------------------

            if(isset($export['format']) && !empty($export['format'])) {
                $format='';
                foreach($export['format'] as $value) {
                    if (defined('STR_BACKOFFICE_EXPORT_FORMAT_'.strtoupper($value)))
                        $texte_format=constant('STR_BACKOFFICE_EXPORT_FORMAT_'.strtoupper($value));
                    else
                        $texte_format=$value;

                    $format.='<a href="'.$session->url($script['name'].'?format='.$value.'&amp;context='.$context['name']).'" target="_blank">'.$texte_format."</a> \n";
                }
                $flux_navig_export.=sprintf($navigation_template_right, $format);
            }
            else {
                $flux_navig_export.=sprintf($navigation_template_right, '&nbsp;');
            }
        }

        //$flux_data.="</div>\n\n";
    }

    //
    // Construction du flux final
    //

    if(!isset($structure['config']['tpl']) || $structure['config']['tpl']=='')
        $structure['config']['tpl']='{filtre}{total}{outside}{data}{navig_export}';

    $flux=$structure['config']['tpl'];

    $flux=str_replace('{filtre}',       $flux_filtre,       $flux);
    $flux=str_replace('{total}',        $flux_total,        $flux);
    $flux=str_replace('{outside}',      $flux_outside,      $flux);
    $flux=str_replace('{data}',         $flux_data,         $flux);
    $flux=str_replace('{navig_export}', $flux_navig_export, $flux);

    if(isset($config['action']['legende']) && $config['action']['legende']==TRUE && isset($flux_legende) && $flux_legende!='')
        $flux=preg_replace('/<div class=\"bo-global\">.*?<\/div>/s', '<div class="bo-local-legende">'.$flux_legende.'</div>', $flux);

    if (!(isset($_GET['ajax']) && $_GET['ajax']=='on')) {
        $js = "
        <script type=\"text/javascript\"><!--
            $(document).ready(function() {
                $('#%s').backoffice('%s', %s, '%s');
                $('#".$context['name']."-collapse-filtre').collapse({
                    toggle:false
                });
                $('#".$context['name']."-collapse-data').collapse({
                    toggle:false
                });              
            });
        // --></script>
        ";

        if(isset($structure['config']['ajax']) && $structure['config']['ajax']==TRUE)
            $js=sprintf($js, $context['name'], $context['name'], 'true', CFG_PATH_HTTP.'/logout.php');
        else
            $js=sprintf($js, $context['name'], $context['name'], 'false', '');

        if($flux!='')
            $flux=$js."\n<div id='".$context['name']."' class='".$config['css']."'>\n".$flux."</div>\n";

    }

    if(isset($structure['js']))
        $flux.=$structure['js'];

    //--------------------------------------------------------------
    //
    // Si le token de session est activ� ((CFG_SESSION_LEVEL&4)==4) et que $config['action'][token']==TRUE
    // On passe le token en GET
    //
    //--------------------------------------------------------------

    if((CFG_SESSION_LEVEL&4)==4 && isset($config['action']['token']) && $config['action']['token']==TRUE)
        $flux=str_replace('?'.$script['action']['label'].'=', '?token='.$session_token.'&amp;'.$script['action']['label'].'=', $flux);

    //--------------------------------------------------------------
    // Fin de traitement
    //--------------------------------------------------------------

    return $flux;
}

/*
 * Fonction navig_string($page, $total_link, $page_link, $balise_number, $script)
 * -----
 * Permet de construire une chaine de navigation page / page
 * -----
 * @param   int         $page                   indice de la page courante
 * @param   int         $total_link             nombre d'�l�ment total
 * @param   int         $page_link              nombre d'�l�ment par page
 * @param   int         $balise_number          nombre de balise de navigation par page
 * @param   string      $script                 nom du script cible
 * -----
 * @return  string      $navig                  la chaine en sortie
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function navig_string($page, $total_link, $page_link, $balise_number, $script) {

    $debut_navig='';
    $fin_navig='';

    if(strstr($script, '?'))
        $script.='&amp;';
    else
        $script.='?';

    if($total_link==0)
        $navig="";
    else {
        $min_page=1;
        $max_page=(int)($total_link/$page_link);

        if(($max_page*$page_link) != $total_link)
            $max_page++;

        $navig='';

        if($page!=$min_page) {
            $debut_navig = '<a href="'.$script.'page=1">&lt;&lt;</a>  ';
            $debut_navig.= '<a href="'.$script.'page='.($page-1).'">&lt;</a> - ';
        }

        if($page!=$max_page) {
            $fin_navig = ' - <a href="'.$script.'page='.($page+1)."\">&gt;</a>\n";
            $fin_navig.= '  <a href="'.$script.'page='.$max_page."\">&gt;&gt;</a>\n";
        }

        if($max_page <= $balise_number) {
            $debut=1;
            $fin=$max_page;
        }
        else {
            $debut=($page)-(int)($balise_number/2);
            $fin=($page)+(int)($balise_number/2);

            while($debut < $min_page) {
                $debut++;
                $fin++;
            }

            while($fin > $max_page) {
                $fin--;
                $debut--;
            }
        }

        while($debut<= $fin) {
            if($debut != $page)
                $navig=$navig.'<a href="'.$script.'page='.($debut).'">'.$debut."</a>\n";
            else
                $navig=$navig.'<span class="badge badge-info">'.$debut."</span>\n";

            if($debut < $fin)
                $navig=$navig.' - ';

            $debut++;
        }

        $navig="\n".$debut_navig.$navig.$fin_navig."\n";
    }
        
    if($navig=="\n<span class=\"badge badge-info\">1</span>\n\n")
        $navig='';

    return $navig;

}

/*
 * Fonction export($format, $donnee, $legende, $couleur)
 * -----
 * Export de donn�es pdf ou excel
 * Attention, pour l'export pdf, les donn�es sont converties au format iso (et non utf-8)
 * -----
 * @param   string      $format                 format (excel ou pdf)
 * @param   array		$donnee                 tableau de donn�es
 * @param   string      $legende                la l�gende
 * @param   string      $couleur				la couleur
 * -----
 * @return  string                              le flux au format d�sir�
 * -----
 * $Author: julienl $
 * $Copyright: GLOBALIS media systems $
 */

function export($format, $donnee, $legende, $couleur) {
    global $structure;
    if($format == 'excel') {
        ob_end_clean();
        $tmp='';
        $tmp.='
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns:o="urn:schemas-microsoft-com:office:office" 
            xmlns:x="urn:schemas-microsoft-com:office:excel" 
            xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <meta http-equiv="Content-type" content="text/html;charset=utf-8" />
                <style id="Classeur1_Styles">
                .excel { mso-number-format:\@; }
                </style>
            </head>
            <body>
            <div id="Classeur1" align=center x:publishsource="Excel">

        ';
        $tmp.='<table border=1px cellpadding=0 cellspacing=0 width=100% style="border-collapse: collapse">';
        $tmp.='<tr>';
        $champ = array();
        foreach($legende as $k => $v){
            if($legende[$k]['export']) {
                $tmp.='<th class=excel nowrap bgcolor="'.$couleur.'">'.$legende[$k]['label'].'</th>';
                $champ[$legende[$k]['field']] = '';
            }
        }
        $tmp.='</tr>';
        foreach($donnee as $k => $v){
            $tmp.='<tr>';
            foreach($champ as $field => $v2){
                $tmp.='<td class=excel nowrap>'.$donnee[$k][$field].'</td>';
            }
            $tmp.='</tr>';
        }
        $tmp.='</table></div></body></html>';

        header('content-disposition: filename='.$structure['context']['name'].'_'.date('YmdHis').'.xls');
        header('content-type: application/ms-excel;');
        echo $tmp;
        exit;
    }elseif($format == 'csv') {
        ob_end_clean();
        $tmp='';
        $champ = array();
        foreach($legende as $k => $v){
            if($legende[$k]['export']) {
                $tmp.="\"".str_replace('"', '\"', $legende[$k]['label'])."\";";
                $champ[$legende[$k]['field']] = '';
            }
        }
        $tmp.="\r\n";
        foreach($donnee as $k => $v){
            foreach($champ as $field => $v2){
                $tmp.="\"".str_replace('"', '\"', $donnee[$k][$field])."\";";
            }
            $tmp.="\r\n";
        }
        header('content-disposition: filename='.$structure['context']['name'].'_'.date('YmdHis').'.csv');
        header('content-type: text/csv; charset=utf-8');
        echo $tmp;
        exit;
    }elseif($format == 'pdf'){
        // 
        // Houston, we've had a problem
        // Cas particulier de l'export PDF
        // La classe FPDF n'impl�mentant pas le support utf8, legende et donn�es sont converties au format iso
        // La classe TCPDF devrait permettre de r�soudre le probl�me
        //
        
        $legende=utf8_decode_mixed($legende);
        $donnee=utf8_decode_mixed($donnee);
                
        // Chargement de fpdf
        require 'fpdf/fpdf.php';
        ob_end_clean();
        define('FPDF_FONTPATH', dirname(__FILE__).'/fpdf/font/');

        class PDF extends FPDF{
            //Chargement des donn�es
            var $widths;
            var $aligns;

            function SetWidths($w) {
                // Tableau des largeurs de colonnes
                $this->widths=$w;
            }

            function SetAligns($a) {
                // Tableau des alignements de colonnes
                $this->aligns=$a;
            }

            function Row($data, $color=0) {

                //Calcule la hauteur de la ligne
                $nb=0;
                for($i=0;$i<count($data);$i++)  {
                    $nb=max($nb,$this->NbLines($this->widths[$i],$data[$i]));
                    //echo $data[$i].' '.$nb.'<br>';
                }
                $h=5*$nb;
                //Effectue un saut de page si n�cessaire
                $this->CheckPageBreak($h);
                //Dessine les cellules
                for($i=0;$i<count($data);$i++) {
                    $w=$this->widths[$i];
                    $a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
                    //Sauve la position courante
                    $x=$this->GetX();
                    $y=$this->GetY();
                    //Dessine le cadre
                    //$this->Rect($x,$y,$w,$h);

                    // ajoute des "\n" si necessaire
                    $nb_current = $this->NbLines($this->widths[$i],$data[$i]);
                    if($nb !=$nb_current) {
                        $data[$i].="\n";
                        for($k=$nb_current; $k<$nb; $k++)
                            $data[$i].="\n";
                    }
                    //Imprime le texte
                    $this->MultiCell($w,5,$data[$i], 'LRTB',$a, $color);
                    //Repositionne � droite
                    $this->SetXY($x+$w,$y);
                }
                //Va � la ligne
                $this->Ln($h);
            }

            function CheckPageBreak($h) {
                //Si la hauteur h provoque un d�bordement, saut de page manuel
                if($this->GetY()+$h>$this->PageBreakTrigger)
                    $this->AddPage($this->CurOrientation);
            }

            function NbLines($w,$txt) {
                //Calcule le nombre de lignes qu'occupe un MultiCell de largeur w
                $cw=&$this->CurrentFont['cw'];
                if($w==0)
                    $w=$this->w-$this->rMargin-$this->x;
                $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
                $s=str_replace("\r",'',$txt);
                $nb=strlen($s);
                if($nb>0 and $s[$nb-1]=="\n")
                    $nb--;
                $sep=-1;
                $i=0;
                $j=0;
                $l=0;
                $nl=1;
                while($i<$nb)
                {
                    $c=$s[$i];
                    if($c=="\n")
                    {
                        $i++;
                        $sep=-1;
                        $j=$i;
                        $l=0;
                        $nl++;
                        continue;
                    }
                    if($c==' ')
                        $sep=$i;
                    $l+=$cw[$c];
                    if($l>$wmax)
                    {
                        if($sep==-1)
                        {
                            if($i==$j)
                                $i++;
                        }
                        else
                            $i=$sep+1;
                        $sep=-1;
                        $j=$i;
                        $l=0;
                        $nl++;
                    }
                    else
                        $i++;
                }
                return $nl;
            }

            function AfficheLib($header, $couleur, $width) {
                //En-t�te
                $couleur = str_replace('#', '', $couleur);
                $r_color = hexdec(substr($couleur, 0, 2));
                $g_color = hexdec(substr($couleur, 2, 2));
                $b_color = hexdec(substr($couleur, 4, 2));

                $this->SetFillColor($r_color,$g_color,$b_color);
                $this->SetTextColor(0);
                $x = $this->GetX();
                $y = $this->GetY();
                foreach($header as $k => $v){
                    $this->SetXY($x,$y);
                    $this->MultiCell($width,7,$header[$k]['label'], 'LTR', 'C', 1);
                    $x += $width;
                }

            }

            function LoadData($header, $donnee) {
                $donnee2 = array();
                foreach($donnee as $k => $v) {
                    $i =0;
                    foreach($header as $k2 => $v2) {
                        $donnee2[$k][$i] = $donnee[$k][$k2];
                        $i++;
                    }
                }
                return $donnee2;
            }
        }

        $header = array();
        $i = 0;
        foreach($legende as $k => $v){
            if($legende[$k]['export']) {
                $header[$legende[$k]['field']]['label'] = $legende[$k]['label'];
                $titre[$i] = $legende[$k]['label'];
                $i++;
            }
        }
        $width = array();
        for($i=0;$i<sizeof($titre);$i++) {
            $width[] = 190/sizeof($titre);
        }

        $pdf = new PDF();
        $pdf->Open();
        $pdf->AddPage();
        $pdf->SetFont('Arial','',9);
        //Chargement des donn�es
        $donnee = $pdf->LoadData($header, $donnee);

        $pdf->SetWidths($width);
        // Affichage des libell�s
        //$pdf->AfficheLib($header,$donnee, $couleur, $width[1]);

        $couleur = str_replace('#', '', $couleur);
        $r_color = hexdec(substr($couleur, 0, 2));
        $g_color = hexdec(substr($couleur, 2, 2));
        $b_color = hexdec(substr($couleur, 4, 2));
        $pdf->SetFillColor($r_color,$g_color,$b_color);
        $pdf->Row($titre, 1);
        // Affichage des donn�es
        for($i=0;$i<sizeof($donnee);$i++) {
            $pdf->Row($donnee[$i], '');
        }
        $pdf->Output($structure['context']['name'].'_'.date('YmdHis').'.pdf', 'I');
    }
}
?>