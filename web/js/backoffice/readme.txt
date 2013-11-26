jquery.backoffice.js


///////////////////////////////
// backoffice_confirm        //
///////////////////////////////


Description
===========

$(element).backoffice_group ( message, options )

Permet d'appliquer au click sur `element` l'ex�cution d'une action de groupe APRES un message de confirmation pass� en param�tre.
Le message de confirmation sera affich� sous forme de notice si le plugin jQuery Notice de Carbone est inclus dans la page.
Dans le cas contraire, une confirmation standard sera utilis�e.

Param�tres
==========
element  : El�ment jQuery repr�sentant un lien d'action de groupe d'une brique backoffice, ou s�lecteur CSS correspondant
message  : Message � afficher dans la bo�te de confirmation.
options  : Options d'affichage si la bo�te de confirmation est affich�e sous forme de Notice. (voir readme de jquery.notice.js)

Exemple d'utilisation
=====================

<script type="text/javascript">
$('.group_del_group').backoffice_group("Confirmer la suppression group�e?");
</script>


///////////////////////////////
// backoffice_group          //
///////////////////////////////


Description
===========

$(element).backoffice_confirm ( message, options )

Permet d'appliquer au click sur `element` l'ex�cution d'une action locale APRES un message de confirmation pass� en param�tre.
Le message de confirmation sera affich� sous forme de notice si le plugin jQuery Notice de Carbone est inclus dans la page.
Dans le cas contraire, une confirmation standard sera utilis�e.
Quelquesoit le type de confirmation, la m�thode `backoffice_message` sera appliqu� au message AVANT l'affichage

Param�tres
==========
element  : El�ment jQuery repr�sentant un lien d'action locale d'une brique backoffice, ou s�lecteur CSS correspondant
message  : Message � afficher dans la bo�te de confirmation.
options  : Options d'affichage si la bo�te de confirmation est affich�e sous forme de Notice. (voir readme de jquery.notice.js)

Exemple d'utilisation
=====================

<script type="text/javascript">
$('.local_del').backoffice_confirm("Confirmer la suppression de %2 %3 ?");
</script>


///////////////////////////////
// backoffice_action_group   //
///////////////////////////////


Description
===========

backoffice_action_group ( element, action )

Permet d'effectuer une action de groupe d'une brique backoffice.
Cette fonction est principalement destin�e � �tre utilis� dans la m�thode backoffice_group.

Param�tres
==========
element : El�ment jQuery repr�sentant un lien d'action de groupe d'une brique backoffice, ou s�lecteur CSS correspondant
action  : URL � transmettre au formulaire d'action de groupe.

Exemple d'utilisation
=====================

<script type="text/javascript">
backoffice_action_group(".group_active_group", "http://192.168.1.28/carbone/utilisateur/index.php?action=active_group");
</script>


///////////////////////////////
// backoffice_message        //
///////////////////////////////


Description
===========

backoffice_message ( element, message )

Retourne une cha�ne format�e � partir d'un message en utilisant des informations d'une brique backoffice correspondant
� la ligne de l'�l�ment html pass� en param�tre.

Param�tres
==========
element : El�ment jQuery d'une brique backoffice, ou s�lecteur CSS correspondant
message : Message � enrichir.

Pour enrichir un message, il faut y ins�rer des marqueurs correspondants aux colonnes de la brique backoffice.
La nomenclature des marqueurs est %n o� n correspond � un num�ro de colonne de la brique backoffice

Exemple d'utilisation
=====================

Prenons un tableau avec la structure suivante:
---------------------------------------------------------
| Id | Prenom   | Nom       | Actions                   |
---------------------------------------------------------
| 1  | Armel    | FAUVEAU   | <a id="del-1">Suppr</a>   |
| 2  | Fred     | HOVART    | <a id="del-2">Suppr</a>   |
| 3  | Arnaud   | BUCHOUX   | <a id="del-3">Suppr</a>   |
| 4  | Julien   | OGER      | <a id="del-4">Suppr</a>   |
---------------------------------------------------------

Cette structure est volontairement simplifi�e par rapport � une brique backoffice normale o� les actions
sont encapsul�es dans un tableau imbriqu� et o� les liens d'actions n'ont pas d'identifiant. 
Malgr� cette subtilit�, le comportement de backoffice_message sera le m�me.

<script type="text/javascript">
var msg = backoffice_message("#del-1", "Confirmer la suppression du compte de %2 %3?");
// msg vaut alors `Confirmer la suppression du compte de Armel FAUVEAU?`

var msg = backoffice_message("#del-4", "Confirmer la suppression du compte n�%1?");
// msg vaut alors `Confirmer la suppression du compte n�4?`
</script>

La fonction n'est efficace que si le param�tre `element` correspond � un seul �l�ment de la brique backoffice.
Si ce param�tre correspond � plusieurs �l�ments html, le message risque d'�tre enrichi avec de mauvaises informations.


///////////////////////////////
// backoffice_row            //
///////////////////////////////


Description
===========

backoffice_row (element)

Retourne un objet JSON avec l'ensemble des �l�ments TD d'une brique backoffice situ� sur la m�me ligne que `element`.
Cette fonction est principalement destin�e � �tre utilis� dans les m�thodes backoffice_message et backoffice_hover.

Param�tres
==========
element : El�ment jQuery d'une brique backoffice ou s�lecteur CSS correspondant

Exemple d'utilisation
=====================

Prenons un tableau avec la structure suivante:
---------------------------------------------------------
| Id | Prenom   | Nom       | Actions                   |
---------------------------------------------------------
| 1  | Armel    | FAUVEAU   | <a id="del-1">Suppr</a>   |
| 2  | Fred     | HOVART    | <a id="del-2">Suppr</a>   |
| 3  | Arnaud   | BUCHOUX   | <a id="del-3">Suppr</a>   |
| 4  | Julien   | OGER      | <a id="del-4">Suppr</a>   |
---------------------------------------------------------

Cette structure est volontairement simplifi�e par rapport � une brique backoffice normale o� les actions
sont encapsul�es dans un tableau imbriqu� et o� les liens d'actions n'ont pas d'identifiant. 
Malgr� cette subtilit�, le comportement de backoffice_row sera le m�me.

<script type="text/javascript">
var content = backoffice_row("#del-1");
</script>

Dans cet exemple la variable content vaudra :
{
1: <td>1</td>,
2: <td>Armel</td>,
3: <td>FAUVEAU</td>,
4: <td><a id="del-1">Suppr</a></td>
}