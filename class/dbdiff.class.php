<?php

/* TODO LIST :
 * - contraintes
 * - mise à jour automatique
 * - choix de mise à jour
 * - formater les messages des paramètre envoyé a smarty
 * - Supprimer tout les getHTML/SQL en les transformant dans smarty (incomplet)
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

    //public function getSQL() {
        //if ($this->name == 'PRIMARY')
            //$retour .= '<br />PRIMARY KEY (`' . $this->colname . '`)';
        //return $retour;
    //}

//}

    public $nom;
    public $coltype;
    public $internom;
    public $interclass;
    public $nullable;
    public $coldefaut;
    public $extra;
    public $commentaire;

    public function getSQL(&$bool) {
    // VIRGULE
    if ($bool) {
        $retour .= '';
        $bool = false;
    } else
        $retour .= ',';

	// TODO changer les () des creates

//	// nullable par defaut 'NULL'
//	if ($this->nullable == 'NO')
//	    $messSQL = array($this->nullable => 'NOT NULL');
//	else
//	    $messSQL = array($this->nullable => '');
//
//	// coldefaut par defaut 'DEFAULT NULL'
//	if ($this->coldefault == 'NULL') $this->coldefault = '';
//	if ($this->colkey == 'PRI') {
//	    $this->colkey = '';
//	    $primary = true;
//	}
//	if ($this->commentaire)
//	    $this->commentaire = 'COMMENT \'' . $this->commentaire . '\'';
//	if ($this->intername) {
//	    $this->intername = 'CONVERT TO CHARACTER SET ' . $this->intername;
//	    $this->interclass = 'COLLATE ' . $this->interclass;
//	}


	$retour .= '<br />`' . $this->name . '` ' . $this->coltype . ' ' .
		$messSQL[$this->nullable] . ' ' . $this->colkey . ' ' .
		$this->coldefault . ' ' . strtoupper($this->extra) . ' ' .
		$this->intername . ' ' . $this->interclass . ' ' . $this->commentaire;
	if ($primary)
	    $retour .= ',<br />PRIMARY KEY (`' . $this->name . '`)';
	return $retour;
    }
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

    public function getHTML() {
        $messHTML = array(diffDb::ACTION_CREATE => '<strong>créer ',
            diffDb::ACTION_DROP => '<strong>supprimer ',
            diffDb::ACTION_ALTER => '<strong>modifier '
        );
        //foreach ($this->constraints as $constraint) {
            //if ($constraint->action != DiffDb::ACTION_SAME && $constraint->action) {
                //if ($constraint->constraint->name == 'PRIMARY')
                    //$retour .= $messHTML[$constraint->action] . ' </strong>la clé primaire<code> ' .
                    //$constraint->constraint->name . '</code><br />';
                //else
                    //$retour .= $messHTML[$constraint->action] . ' </strong>la clé étrangère<code> ' .
                    //$constraint->constraint->name . '</code><br />';
                //$retour .= $constraint->getHTML();
            //}
        //}
        foreach ($this->fields as $field) {
            if ($field->action != DiffDb::ACTION_SAME && $field->action) {
                $retour .= $messHTML[$field->action] . '</strong>le champ : <code>' . $field->field->name . '</code><br />';
                $retour .= $field->getHTML();
            }
        }
        return $retour;
    }

    public function getSQL() {
        $messSQL = array(diffDb::ACTION_CREATE => 'ADD COLUMN ',
            diffDb::ACTION_DROP => 'DROP COLUMN ',
            diffDb::ACTION_ALTER => 'MODIFY COLUMN '
        );
        $bool = true;
        foreach ($this->fields as $field) {
            if ($field->action != DiffDb::ACTION_SAME) {
                if ($bool) {
                    $bool = false;
                } else
                    $retour .= ',';
                if ($field->field->name) {
                    // Si c'est un DiffField
                    if ($field->action != DiffDb::ACTION_DROP)
                        $retour .= '<br />' . $messSQL[$field->action] . ' `' . $field->field->name . '` ' . $field->getSQL();
                    else
                        $retour .= '<br />' . $messSQL[$field->action] . ' `' . $field->field->name . '`';
                }
                // Sinon c'est un Field
                else
                    $retour .= '<br />' . $field->getSQL($bool);
            }
        }
        //foreach ($this->constraints as $constraint) {
            //if ($constraint->action != DiffDb::ACTION_SAME) {
                //if ($bool) {
                    //$bool = false;
                //} else
                    //$retour .= ',';
                //$retour .= '<br />' . $constraint->getSQL();
            //}
        //}
        return $retour;
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

    //public function getHTML() {
        //$retour .= 'Avec la colonne : <code>' . $this->constraint->colname . '</code><br />';
        //return $retour;
    //}

    //public function getSQL() {
        //if ($this->action == diffdb::ACTION_CREATE && $this->constraint->name == 'PRIMARY KEY')
            //$retour .= '<br />PRIMARY KEY (`' . $this->constraint->colname . '`)';
        //else
            //$retour .= '<br />KEY';
        //return $retour;
    //}
//}

?>
