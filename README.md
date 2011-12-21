# Dbdiff - Comparateur de base de données

Travail réalisé durant mon stage de première année.
Repris pour la PTI objet.

## Dépendances

PHP 5.X.X
Smarty

## Installation de smarty

Smarty a besoin de quatre répertoires qui sont, par défaut, 'templates/',
'templates_c/', 'configs/' et 'cache/'. Placez vous dans votre répértoire puis
créez les avec :

    mkdir templates
    mkdir templates_c
    mkdir configs
    mkdir cache

Smarty a besoin d'accéder en écriture aux répertoires templates_c et cache, il
faut donc régler les permissions d'accès (exemple) :

    chown www:www templates_c/
    chmod 770 templates_c/
    chown www:www cache/
    chmod 770 cache/

[voir la documentation de smarty](http://www.smarty.net/docsv2/fr/installing.smarty.basic.tpl)
