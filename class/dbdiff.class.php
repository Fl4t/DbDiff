<?php

/* TODO LIST :
 * - contraintes
 * - mise à jour automatique
 * - choix de mise à jour
 */

require_once 'sql.class.php';

class Bdd {

    protected $tableauDeTables = array();

    public function __construct($serveur, $login, $mdp, $bddNom) {
        $connexion = new SQL('mysql', $serveur, $login, $mdp, 'INFORMATION_SCHEMA');
        $this->tableauDeTables = $connexion->execToClasses('Table',
                                                           'SELECT TABLE_NAME nom
                                                           FROM INFORMATION_SCHEMA.TABLES
                                                           WHERE TABLE_SCHEMA = :bdd',
                                                           array('bdd' => $bddNom));
        foreach ($this->tableauDeTables as $nomTable) {
            $nomTable->setTableauDeChamps($connexion->execToClasses('Champ',
                                                                    'SELECT column_name nom,
                                                                    column_type coltype,
                                                                    if(is_nullable = \'NO\',\'NOT NULL\',\'NULL\') nullable
                                                                    FROM INFORMATION_SCHEMA.COLUMNS
                                                                    WHERE table_schema = :bdd AND table_name = :table
                                                                    ORDER BY nom',
                                                                    array('bdd' => $bddNom,
                                                                    'table' => $nomTable->getNom())));
        }
    }

    public function getTableauDeTables() {
        return $this->tableauDeTables;
    }

}

class Table {

    protected $nom;
    protected $tableauDeChamps = array();

    public function getNom() {
        return $this->nom;
    }

    public function getTableauDeChamps() {
        return $this->tableauDeChamps;
    }

    public function setTableauDeChamps($prmTab) {
        $this->tableauDeChamps = $prmTab;
    }
}

class Champ {

    protected $nom;
    protected $coltype;
    protected $nullable;

    // Accesseur
    public function getNom() {
        return $this->nom;
    }

    public function getColtype() {
        return $this->coltype;
    }

    public function getNullable() {
        return $this->nullable;
    }
}

class DiffBdd {

    protected $tableauDeDiffs = array();

    const ACTION_SAME = 1;
    const ACTION_CREATE = 2;
    const ACTION_DROP = 3;
    const ACTION_ALTER = 4;

    public function __construct($bddDeRef, $bddAMaJ) {
        $tablesDeRef = $bddDeRef->getTableauDeTables();
        $tablesAMaJ = $bddAMaJ->getTableauDeTables();
        reset($tablesDeRef);
        reset($tablesAMaJ);
        while (current($tablesDeRef) || current($tablesAMaJ)) {
            $curDiffTable = new DiffTable(current($tablesDeRef), current($tablesAMaJ));
            switch ($curDiffTable->getAction()) {
            case DiffBdd::ACTION_CREATE:
                $this->tableauDeDiffs[] = $curDiffTable;
                next($tablesDeRef);
                break;
            case DiffBdd::ACTION_DROP:
                $this->tableauDeDiffs[] = $curDiffTable;
                next($tablesAMaJ);
                break;
            case DiffBdd::ACTION_ALTER:
                $this->tableauDeDiffs[] = $curDiffTable;
            default:
                next($tablesDeRef);
                next($tablesAMaJ);
            }
        }
    }

    public function getTableauDeDiffs() {
        return $this->tableauDeDiffs;
    }

}

class DiffTable {

    protected $nom;
    protected $action = Diffbdd::ACTION_SAME;
    protected $tableauDeChamps = array();

    public function __construct($tableDeRef, $tableAMaJ) {
        $intComparaison = strcmp((is_object($tableDeRef) ? $tableDeRef->getNom() : null),
                                 (is_object($tableAMaJ) ? $tableAMaJ->getNom() : null));
        if (($intComparaison < 0 && $tableDeRef) || $tableAMaJ == NULL) {
            $this->nom = $tableDeRef->getNom();
            $this->action = DiffBdd::ACTION_CREATE;
            $this->tableauDeChamps = $tableDeRef->getTableauDeChamps();
        } elseif (($intComparaison > 0 && $tableAMaJ) || $tableDeRef == NULL) {
            $this->nom = $tableAMaJ->getNom();
            $this->action = DiffBdd::ACTION_DROP;
        } else {
            $this->nom = $tableDeRef->getNom();
            $this->CompareLesChamps($tableDeRef->getTableauDeChamps(), $tableAMaJ->getTableauDeChamps());
        }
    }

    public function getNom() {
        return $this->nom;
    }

    public function getAction() {
        return $this->action;
    }

    public function getTableauDeChamps() {
        return $this->tableauDeChamps;
    }

    protected function CompareLesChamps($champsDeRef, $champsAMaJ) {
        reset($champsDeRef);
        reset($champsAMaJ);
        while (current($champsDeRef) || current($champsAMaJ)) {
            $curDiffChamp = new DiffChamp(current($champsDeRef), current($champsAMaJ));
            switch ($curDiffChamp->getAction()) {
            case DiffBdd::ACTION_CREATE:
                $this->action = DiffBdd::ACTION_ALTER;
                $this->tableauDeChamps[] = $curDiffChamp;
                next($champsDeRef);
                break;
            case DiffBdd::ACTION_DROP:
                $this->action = DiffBdd::ACTION_ALTER;
                $this->tableauDeChamps[] = $curDiffChamp;
                next($champsAMaJ);
                break;
            case DiffBdd::ACTION_ALTER:
                $this->action = DiffBdd::ACTION_ALTER;
                $this->tableauDeChamps[] = $curDiffChamp;
            default:
                next($champsDeRef);
                next($champsAMaJ);
            }
        }
    }

}

class DiffChamp {

    protected $action = Diffbdd::ACTION_SAME;
    protected $champ;

    public function __construct($champDeRef, $champAMaJ) {
        $intComparaison = strcmp((is_object($champDeRef) ? $champDeRef->getNom() : null),
                                 (is_object($champAMaJ) ? $champAMaJ->getNom() : null));
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

    public function getAction() {
        return $this->action;
    }

    public function getChamp() {
        return $this->champ;
    }

    public function setAction($prmAction) {
        $this->action = $prmAction;
    }
}

?>
