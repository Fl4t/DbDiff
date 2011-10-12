<?php

require_once("class/Smarty.class.php");
require_once("class/dbdiff.class.php");

if (isset($_POST['db-host1'])) {
    $dbRef = new iit_DB($_POST['db-host1'], $_POST['db-user1'], $_POST['db-password1'], $_POST['db-name1']);
    $dbMaJ = new iit_DB($_POST['db-host2'], $_POST['db-user2'], $_POST['db-password2'], $_POST['db-name2']);
    $dbdiff = new DiffDb($dbRef, $dbMaJ);

    $messHTML = array(diffDb::ACTION_CREATE => 'créer ',
	diffDb::ACTION_DROP => 'supprimer ',
	diffDb::ACTION_ALTER => 'modifier '
    );

    $messSQL = array(diffDb::ACTION_CREATE => 'CREATE TABLE IF NOT EXISTS ',
	diffDb::ACTION_DROP => 'DROP TABLE ',
	diffDb::ACTION_ALTER => 'ALTER TABLE '
    );
}

$smarty = new Smarty();
$smarty->assign('dbdiff', $dbdiff);
$smarty->assign('messHTML', $messHTML);
$smarty->assign('messSQL', $messSQL);
$smarty->display('index.html');
?>
