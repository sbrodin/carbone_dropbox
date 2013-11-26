jquery.notice.js

Ce fichier contient trois m�thodes (notice, noticeRemove et confirm).
La premi�re propose une impl�mentation simple d'affichage de bo�tes modales.
La deuxi�me de retirer toute modale (notice ou confirm) qui est pr�sente dans la page.
La derni�re propose une alternative au f�netre de confirmation et pour la derni�re.

///////////////////
// Notice        //
///////////////////

Description
===========

$.notice(element, options)
ou
$(element).notice(options)

Permet d'afficher un �l�ment html dans une modale HTML. Cette fonction ne permet pas d'afficher plusieurs modales en m�me temps.

Param�tres
==========
element : El�ment jQuery (ou son s�lecteur CSS) OU code html � afficher dans la modale.
options : Tableau JSON de param�tres pour personnaliser l'affichage de la modale.

Les options possibles sont les suivantes :

cl�         type        explication
---------------------------------------------------------------------------------------
close       bool        Param�tre l'affichage ou non d'une DIV permettant de fermer la modale. TRUE par d�faut.
                        Cette DIV aura pour classe CSS `close` (Cette class n'est pas param�trable).

duration    int         D�termine la dur�e d'affichage de la modale en millisecondes.
                        Cette valeur vaut `0` par d�faut ce qui signifie que l'affichage est permanent.

height      int/bool    Permet de forcer la hauteur (en pixel) de la modale.
                        Cette valeur est � FALSE par d�faut. La hauteur d�pendra alors de la hauteur de l'�l�ment � afficher
                        ou d'une propri�t� CSS `height` appliqu�e � la classe de la modale.

width       int/bool    Permet de forcer la largeur (en pixel) de la modale.
                        Cette valeur est � FALSE par d�faut. La largeur d�pendra alors de la largeur de l'�l�ment � afficher
                        ou d'une propri�t� CSS `width` appliqu�e � la classe de la modale.

center      bool        Permet de forcer l'affichage de la modale au centre de la fen�tre. TRUE par d�faut.
                        Si la valeur vaut FALSE, le positionnement de la modale devra �tre g�r� avec les CSS.

overlay     str/bool    D�termine la classe CSS de la zone d'overlay qui sera affich�e derri�re la modale
                        Si cette valeur vaut FALSE, la zone d'overlay ne sera pas cr��e.
                        Par d�faut cette valeur vaut FALSE.

className   str/bool    D�termine la classe CSS de la modale.
                        Par d�faut cette valeur vaut `notice-item`.

Exemple d'utilisation
=====================


<script type="text/javascript">

$('<div>Message � caract�re informatif</div>').notice({
    close:      FALSE,
    duration:   3000,
    height:     200,
    width:      400,
    center:     FALSE,
    overlay:    'notice-overlay',
    className:  'notice-left'
});

</script>

///////////////////
// noticeRemove  //
///////////////////

Descriptions
============

$.noticeRemove()

Permet de retirer une fen�tre modale pr�sente dans la page.

Exemple d'utilisation
=====================

<script type="text/javascript">

$('.close').click(function() {
    $.noticeRemove();
});

ou plus simplement

$('.close').click($.noticeRemove);

</script>

///////////////////
// Confirm       //
///////////////////

Description
===========

$.confirm(message, callback, options)

Affiche une fen�tre modale de type confirmation. Cette modale contiendra un message et deux boutons d'actions (Ok, Annuler)
Lorsque l'utilisateur clique sur un des boutons, une fonction de callback est ex�cut�e en lui passant comme param�tre, la r�ponse de l'utilisateur (TRUE pour Ok et FALSE pour Annuler).

Param�tres
==========
message     : Message qui sera affich�e dans la modale de confirmation
callback    : Fonction qui sera ex�cut�e apr�s le click sur un bouton d'action avec en param�tre le r�sultat de la confirmation.
options     : Tableau JSON de param�tres pour personnaliser le comportement et l'affichage de la modale.

Toutes les options de la m�thode notice pour disponible pour la m�thode confirm. Voici tout de m�me quelques diff�rences:

cl�         type        explication
---------------------------------------------------------------------------------------
close       bool        Contrairement � notice, ce param�tre vaut FALSE par d�faut.

strOk       string      Permet de modifier le libell� du bouton "Ok"

strCancel   string      Permet de modifier le libell� du bouton "Annuler"
                        
Exemple d'utilisation
=====================

<script type="text/javascript">
$(function() {

    $('.bt_lambda').confirm('Confirmer l\'action ?', function(response) {
        if(response == true) {
            // do something if user click on 'Ok' button
        } else {
            // do something else if user click on 'Annuler' button
        }
    });

});
</script>