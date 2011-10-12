<?php

/* TODO LIST :
 * - contraintes
 * - mise à jour automatique
 * - choix de mise à jour
 * - formater les messages des paramètre envoyé a smarty
 * - Supprimer tout les getHTML/SQL en les transformant dans smarty (incomplet)
 */

require_once 'iit_SQLQuery.php';

class iit_DB {

    public $tables = null;

    public function __construct($server, $login, $passwd, $dbname) {
        $connexion = new iit_SQLQuery('mysql', $server, $login, $passwd, 'INFORMATION_SCHEMA');
        $this->tables = $connexion->execToClasses(
            'Table', 'SELECT `TABLE_NAME` name
            FROM INFORMATION_SCHEMA.TABLES
            WHERE `TABLE_SCHEMA` = :bdd', array('bdd' => $dbname));
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
            $nomtable->fields = $connexion->execToClasses(
                'Field', 'SELECT column_name name,
                column_type coltype,
                character_set_name intername,
                collation_name interclass,
                is_nullable nullable,
                column_default coldefault,
                extra,
                column_comment commentaire
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE table_schema = :bdd
                AND table_name = :table', array('bdd' => $dbname, 'table' => $nomtable->name));
        }
    }

}

class Table {

    public $name;
    //public $constraints;
    public $fields;

}

//class Constraint {

    //public $name;
    //public $constype;
    //public $colname;

    //public function getSQL() {
        //if ($this->name == 'PRIMARY')
            //$retour .= '<br />PRIMARY KEY (`' . $this->colname . '`)';
        //return $retour;
    //}

//}

class Field {

    public $name;
    public $coltype;
    public $intername;
    public $interclass;
    public $nullable;
    public $coldefault;
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

class DiffDb {

    public $diffs = array();

    CONST ACTION_SAME = 1;
    CONST ACTION_CREATE = 2;
    CONST ACTION_DROP = 3;
    CONST ACTION_ALTER = 4;

    public function __construct($dbRef, $dbMaJ) {
        reset($dbRef->tables);
        reset($dbMaJ->tables);
        while (current($dbRef->tables) || current($dbMaJ->tables)) {
            $curDiffTable = new DiffTable(current($dbRef->tables), current($dbMaJ->tables));
            $this->diffs[] = $curDiffTable;
            switch ($curDiffTable->action) {
            case DiffDb::ACTION_CREATE:
                next($dbRef->tables);
                break;
            case DiffDb::ACTION_DROP:
                next($dbMaJ->tables);
                break;
            default:
                next($dbRef->tables);
                next($dbMaJ->tables);
            }
        }
    }

}

class DiffTable {

    public $name;
    public $action = diffdb::ACTION_SAME;
    public $fields = array();

    public function __construct($tableRef, $tableMaJ) {
        $intComparaison = strcmp($tableRef->name, $tableMaJ->name);
        if (($intComparaison < 0 && $tableRef) || $tableMaJ == NULL) {
            $this->name = $tableRef->name;
            $this->constraints = $tableRef->constraints;
            $this->action = DiffDb::ACTION_CREATE;
            $this->fields = $tableRef->fields;
        } elseif (($intComparaison > 0 && $tableMaJ) || $tableRef == NULL) {
            $this->name = $tableMaJ->name;
            $this->action = DiffDb::ACTION_DROP;
        } else {
            $this->name = $tableRef->name;
            //$this->CompareConsts($tableRef->constraints, $tableMaJ->constraints);
            $this->CompareFields($tableRef->fields, $tableMaJ->fields);
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

    protected function CompareFields($FieldsRef, $FieldsMaJ) {
        reset($FieldsRef);
        reset($FieldsMaJ);
        while (current($FieldsRef) || current($FieldsMaJ)) {
            $curDiffField = new DiffField(current($FieldsRef), current($FieldsMaJ));
            $this->fields[] = $curDiffField;
            switch ($curDiffField->action) {
            case DiffDb::ACTION_CREATE:
                $this->action = DiffDb::ACTION_ALTER;
                next($FieldsRef);
                break;
            case DiffDb::ACTION_DROP:
                $this->action = DiffDb::ACTION_ALTER;
                next($FieldsMaJ);
                break;
            default:
                if ($curDiffField->action == DiffDb::ACTION_ALTER)
                    $this->action = DiffDb::ACTION_ALTER;
                next($FieldsRef);
                next($FieldsMaJ);
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

class DiffField {

    public $action = diffdb::ACTION_SAME;
    public $field = null;

    public function __construct($FieldRef, $FieldMaJ) {
        $intComparaison = strcmp($FieldRef->name, $FieldMaJ->name);
        if (($intComparaison < 0 && $FieldRef) || $FieldMaJ == NULL) {
            $this->field = $FieldRef;
            $this->action = DiffDb::ACTION_CREATE;
        } elseif (($intComparaison > 0 && $FieldMaJ) || $FieldRef == NULL) {
            $this->field = $FieldMaJ;
            $this->action = DiffDb::ACTION_DROP;
        } else {
            $this->field = $FieldRef;
            if ($FieldRef != $FieldMaJ)
                $this->action = DiffDb::ACTION_ALTER;
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
