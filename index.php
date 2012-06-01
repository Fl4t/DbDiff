<?php

// définition de la constante Smarty
define('SMARTY_DIR', './Smarty-git/libs/');

require_once(SMARTY_DIR.'Smarty.class.php');
require_once('class/dbdiff.class.php');

$bddDiff = null;
$messHTMLTable = null;
$messHTMLChamp = null;
$messSQLTable = null;
$messSQLChamp = null;

if (isset($_POST['bdd-host1'])) {
  $bddRef = new Bdd($_POST['bdd-host1'], $_POST['bdd-user1'], $_POST['bdd-password1'], $_POST['bdd-nom1']);
  $bddMaJ = new Bdd($_POST['bdd-host2'], $_POST['bdd-user2'], $_POST['bdd-password2'], $_POST['bdd-nom2']);
  $bddDiff = new DiffBdd($bddRef, $bddMaJ);

  $messHTMLTable = array(DiffBdd::ACTION_CREATE => 'créer ',
    DiffBdd::ACTION_DROP => 'supprimer ',
    DiffBdd::ACTION_ALTER => 'modifier '
  );

  $messHTMLChamp = array(NULL => 'avec ',
    DiffBdd::ACTION_CREATE => 'créer ',
    DiffBdd::ACTION_DROP => 'supprimer ',
    DiffBdd::ACTION_ALTER => 'modifier '
  );

  $messSQLTable = array(DiffBdd::ACTION_CREATE => 'CREATE TABLE IF NOT EXISTS ',
    DiffBdd::ACTION_DROP => 'DROP TABLE ',
    DiffBdd::ACTION_ALTER => 'ALTER TABLE '
  );

  $messSQLChamp = array(DiffBdd::ACTION_CREATE => 'ADD ',
    DiffBdd::ACTION_DROP => 'DROP COLUMN ',
    DiffBdd::ACTION_ALTER => 'MODIFY COLUMN '
  );
}

$smarty = new Smarty();
$smarty->assign('bddDiff', $bddDiff);
$smarty->assign('messHTMLTable', $messHTMLTable);
$smarty->assign('messHTMLChamp', $messHTMLChamp);
$smarty->assign('messSQLTable', $messSQLTable);
$smarty->assign('messSQLChamp', $messSQLChamp);
$smarty->display('index.html');

?>
