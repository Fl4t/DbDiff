<?php

/* TODO LIST :
 * - contraintes
 * - mise à jour automatique
 * - choix de mise à jour
 */

require_once 'sql.class.php';

class Bdd {

    public $tables = null;

    public function __construct($serveur, $login, $mdp, $bddnom) {
        $connexion = new SQL('mysql', $serveur, $login, $mdp, 'INFORMATION_SCHEMA');
        $this->tables = $connexion->execToClasses(
            'Table', 'SELECT TABLE_NAME nom
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = :bdd', array('bdd' => $bddnom));
        foreach ($this->tables as $nomtable) {
            //$nomtable->constraints = $connexion->execToClasses(
                //'Constraint', 'SELECT k.constraint_name name,
                //t.constraint_type constype,
                //k.column_name colname
                //FROM information_schema.key_column_usage k
                //INNER JOIN information_schema.table_constraints t
                //ON k.constraint_name = t.constraint_name
                //WHERE k.constraint_schema = :bdd
                //AND k.table_name = :table
                //GROUP BY .column_name', array('bdd' => $dbname, 'table' => $nomtable->name));
            $nomtable->champs = $connexion->execToClasses(
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
                AND table_name = :table', array('bdd' => $bddnom, 'table' => $nomtable->nom));
        }
    }

}

class Table {

    //public $constraints;
    public $nom;
    public $champs;

}

//class Constraint {
class Champ {

    //public $name;
    //public $constype;
    //public $colname;


//}

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

    public $diffs = array();

    CONST ACTION_SAME = 1;
    CONST ACTION_CREATE = 2;
    CONST ACTION_DROP = 3;
    CONST ACTION_ALTER = 4;

    public function __construct($bddRef, $bddMaJ) {
        reset($bddRef->tables);
        reset($bddMaJ->tables);
        while (current($bddRef->tables) || current($bddMaJ->tables)) {
            $curDiffTable = new DiffTable(current($bddRef->tables), current($bddMaJ->tables));
            $this->diffs[] = $curDiffTable;
            switch ($curDiffTable->action) {
            case DiffBdd::ACTION_CREATE:
                next($bddRef->tables);
                break;
            case DiffBdd::ACTION_DROP:
                next($bddMaJ->tables);
                break;
            default:
                next($bddRef->tables);
                next($bddMaJ->tables);
            }
        }
    }

}

class DiffTable {

    public $nom;
    public $action = Diffbdd::ACTION_SAME;
    public $champs = array();

    public function __construct($tableRef, $tableMaJ) {
        $intComparaison = strcmp($tableRef->nom, $tableMaJ->nom);
        if (($intComparaison < 0 && $tableRef) || $tableMaJ == NULL) {
            $this->nom= $tableRef->nom;
            $this->constraints = $tableRef->constraints;
            $this->action = DiffBdd::ACTION_CREATE;
            $this->champs = $tableRef->champs;
        } elseif (($intComparaison > 0 && $tableMaJ) || $tableRef == NULL) {
            $this->nom= $tableMaJ->nom;
            $this->action = DiffBdd::ACTION_DROP;
        } else {
            $this->nom= $tableRef->nom;
            //$this->CompareConsts($tableRef->constraints, $tableMaJ->constraints);
            $this->CompareChamps($tableRef->champs, $tableMaJ->champs);
        }
    }

    //protected function CompareConsts($ConstsRef, $ConstsMaJ) {
        //reset($ConstsRef);
        //reset($ConstsMaJ);
        //while (current($ConstsRef) || current($ConstsMaJ)) {
            //$curDiffConst = new DiffConst(current($ConstsRef), current($ConstsMaJ));
            //$this->constraints[] = $curDiffConst;
            //switch ($curDiffConst->action) {
            //case DiffDb::ACTION_CREATE:
                //$this->action = DiffDb::ACTION_ALTER;
                //next($ConstsRef);
                //break;
            //case DiffDb::ACTION_DROP:
                //$this->action = DiffDb::ACTION_ALTER;
                //next($ConstsMaJ);
                //break;
            //default:
                //if ($curDiffConst->action == DiffDb::ACTION_ALTER)
                    //$this->action = DiffDb::ACTION_ALTER;
                //next($ConstsRef);
                //next($ConstsMaJ);
            //}
        //}
    //}

    protected function CompareChamps($champsRef, $champsMaJ) {
        reset($champsRef);
        reset($champsMaJ);
        while (current($champsRef) || current($champsMaJ)) {
            $curDiffChamp = new DiffChamp(current($champsRef), current($champsMaJ));
            $this->champs[] = $curDiffChamp;
            switch ($curDiffChamp->action) {
            case DiffBdd::ACTION_CREATE:
                $this->action = DiffBdd::ACTION_ALTER;
                next($champsRef);
                break;
            case DiffBdd::ACTION_DROP:
                $this->action = DiffBdd::ACTION_ALTER;
                next($champsMaJ);
                break;
            default:
                if ($curDiffChamp->action == DiffBdd::ACTION_ALTER)
                    $this->action = DiffBdd::ACTION_ALTER;
                next($champsRef);
                next($champsMaJ);
            }
        }
    }

}

class DiffChamp {

    public $action = Diffbdd::ACTION_SAME;
    public $champ = null;

    public function __construct($champRef, $champMaJ) {
        $intComparaison = strcmp($champRef->nom, $champMaJ->nom);
        if (($intComparaison < 0 && $champRef) || $champMaJ == NULL) {
            $this->champ = $champRef;
            $this->action = DiffBdd::ACTION_CREATE;
        } elseif (($intComparaison > 0 && $champMaJ) || $champRef == NULL) {
            $this->champ = $champMaJ;
            $this->action = DiffBdd::ACTION_DROP;
        } else {
            $this->champ = $champRef;
            if ($champRef != $champMaJ)
                $this->action = DiffBdd::ACTION_ALTER;
        }
    }
}

//class DiffConst {

    //public $action = diffdb::ACTION_SAME;
    //public $constraint = null;

    //public function __construct($ConstRef, $ConstMaJ) {
        //$intComparaison = strcmp($ConstRef->name, $ConstMaJ->name);
        //if (($intComparaison < 0 && $ConstRef) || $ConstMaJ == NULL) {
            //$this->constraint = $ConstRef;
            //$this->action = DiffDb::ACTION_CREATE;
        //} elseif (($intComparaison > 0 && $ConstMaJ) || $ConstRef == NULL) {
            //$this->constraint = $ConstMaJ;
            //$this->action = DiffDb::ACTION_DROP;
        //} else {
            //$this->constraint = $ConstRef;
            //if ($ConstRef != $ConstMaJ)
                //$this->action = DiffDb::ACTION_ALTER;
        //}
    //}
//}

?>
