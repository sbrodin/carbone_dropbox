<?php
/*
 * Fonction rename_example($data)
 * -----
 * G�n�ration d'un nom de fichier (pour renomage apr�s upload)
 * -----
 * @param   string      $data                   nom du champ upload
 * -----
 * @return  string                              nom du fichier final
 * -----
 * $Author: armel $
 * $Copyright: GLOBALIS media systems $
 */

function rename_example($data) {
    return strtolower(microtime(TRUE).strrchr($_FILES[$data.'_tmp']['name'], '.'));
}
?>