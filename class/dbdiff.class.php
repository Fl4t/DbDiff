<?php

/* TODO LIST :
 * - contraintes
 * - mise à jour automatique
 * - choix de mise à jour
 */

require_once 'sql.class.php';

class Bdd {

    public $tableauDeTables = null;

    public function __construct($serveur, $login, $mdp, $bddNom) {
        $connexion = new SQL('mysql', $serveur, $login, $mdp, 'INFORMATION_SCHEMA');
        $this->tableauDeTables = $connexion->execToClasses(
            'Table', 'SELECT TABLE_NAME nom
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = :bdd', array('bdd' => $bddNom));
        foreach ($this->tableauDeTables as $nomTable) {
            $nomTable->tableauDeChamps = $connexion->execToClasses(
                'Champ', 'SELECT column_name nom,
                column_type coltype,
                character_set_name internom,
                collation_name interclass,
                is_nullable nullable,
                column_default coldefaut,
                extra,
                column_comment commentaire
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE table_schema = :bdd
                AND table_name = :table', array('bdd' => $bddNom, 'table' => $nomTable->nom));
        }
    }

}

class Table {

    public $nom;
    public $tableauDeChamps;

}

class Champ {

    public $nom;
    public $coltype;
    public $internom;
    public $interclass;
    public $nullable;
    public $coldefaut;
    public $extra;
    public $commentaire;

}

class DiffBdd {

    public $tableauDeDiffs = array();

    CONST ACTION_SAME = 1;
    CONST ACTION_CREATE = 2;
    CONST ACTION_DROP = 3;
    CONST ACTION_ALTER = 4;

    public function __construct($bddDeRef, $bddAMaJ) {
        reset($bddDeRef->tableauDeTables);
        reset($bddAMaJ->tableauDeTables);
        while (current($bddDeRef->tableauDeTables) || current($bddAMaJ->tableauDeTables)) {
            $curDiffTable = new DiffTable(current($bddDeRef->tableauDeTables), current($bddAMaJ->tableauDeTables));
            $this->tableauDeDiffs[] = $curDiffTable;
            switch ($curDiffTable->action) {
            case DiffBdd::ACTION_CREATE:
                next($bddDeRef->tableauDeTables);
                break;
            case DiffBdd::ACTION_DROP:
                next($bddAMaJ->tableauDeTables);
                break;
            default:
                next($bddDeRef->tableauDeTables);
                next($bddAMaJ->tableauDeTables);
            }
        }
    }

}

class DiffTable {

    public $nom;
    public $action = Diffbdd::ACTION_SAME;
    public $tableauDeChamps = array();

    public function __construct($tableDeRef, $tableAMaJ) {
        $intComparaison = strcmp($tableDeRef->nom, $tableAMaJ->nom);
        if (($intComparaison < 0 && $tableDeRef) || $tableAMaJ == NULL) {
            $this->nom = $tableDeRef->nom;
            $this->action = DiffBdd::ACTION_CREATE;
            $this->tableauDeChamps = $tableDeRef->tableauDeChamps;
        } elseif (($intComparaison > 0 && $tableAMaJ) || $tableDeRef == NULL) {
            $this->nom = $tableAMaJ->nom;
            $this->action = DiffBdd::ACTION_DROP;
        } else {
            $this->nom = $tableDeRef->nom;
            $this->CompareLesChamps($tableDeRef->tableauDeChamps, $tableAMaJ->tableauDeChamps);
        }
    }

    protected function CompareLesChamps($champsDeRef, $champsAMaJ) {
        reset($champsDeRef);
        reset($champsAMaJ);
        while (current($champsDeRef) || current($champsAMaJ)) {
            $curDiffChamp = new DiffChamp(current($champsDeRef), current($champsAMaJ));
            $this->tableauDeChamps[] = $curDiffChamp;
            switch ($curDiffChamp->action) {
            case DiffBdd::ACTION_CREATE:
                $this->action = DiffBdd::ACTION_ALTER;
                next($champsDeRef);
                break;
            case DiffBdd::ACTION_DROP:
                $this->action = DiffBdd::ACTION_ALTER;
                next($champsAMaJ);
                break;
            default:
                if ($curDiffChamp->action == DiffBdd::ACTION_ALTER)
                    $this->action = DiffBdd::ACTION_ALTER;
                next($champsDeRef);
                next($champsAMaJ);
            }
        }
    }

}

class DiffChamp {

    public $action = Diffbdd::ACTION_SAME;
    public $champ = null;

    public function __construct($champDeRef, $champAMaJ) {
        $intComparaison = strcmp($champDeRef->nom, $champAMaJ->nom);
        if (($intComparaison < 0 && $champDeRef) || $champAMaJ == NULL) {
            $this->champ = $champDeRef;
            $this->action = DiffBdd::ACTION_CREATE;
        } elseif (($intComparaison > 0 && $champAMaJ) || $champDeRef == NULL) {
            $this->champ = $champAMaJ;
            $this->action = DiffBdd::ACTION_DROP;
        } else {
            $this->champ = $champDeRef;
            if ($champDeRef != $champAMaJ)
                $this->action = DiffBdd::ACTION_ALTER;
        }
    }
}

?>
