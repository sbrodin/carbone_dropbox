Plugin � appliquer sur un champ select multiple.

Usage :

Par d�faut (simple click pour faire transiter les �l�ments et on garde l'ordre)
$('#id').multiselect();

Double click pour faire transiter les �l�ments 
$('#id').multiselect({event:'dblclick'});

On ne garde pas l'ordre
$('#id').multiselect({order:false});

Combinaison des 2 param�trages pr�c�dents
$('#id').multiselect({event:'dblclick',order:false});

On assigne une classe CSS diff�rentes pour les 2 containers
$('#id').multiselect({selectClass:'other',cloneClass:'other_clone'});