<?php

include("../inc/inc.Settings.php");
include("../inc/inc.LogInit.php");
include("../inc/inc.Utils.php");
include("../inc/inc.Language.php");
include("../inc/inc.Init.php");
include("../inc/inc.Extension.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassAcl.php");
include("../inc/inc.Authentication.php");

if (!$user->isAdmin()) {
	$result = array('type'=>'error', 'msg'=>getMLText("access_denied"));
	echo json_encode($result);
	exit;
}

if (isset($_GET["action"])) $action=$_GET["action"];
else $action=NULL;

if($action == 'add_aro') {
	if (isset($_GET["roleid"])) {
		if(!($role = SeedDMS_Core_Role::getInstance((int) $_GET["roleid"], $dms))) {
			$result = array('type'=>'error', 'msg'=>getMLText("access_denied"));
			echo json_encode($result);
			exit;
		}
	} else {
		$result = array('type'=>'error', 'msg'=>getMLText("access_denied"));
		echo json_encode($result);
		exit;
	}

} else {
	if (isset($_GET["aroid"])) {
		if(!($aro = SeedDMS_Aro::getInstance((int) $_GET["aroid"], $dms))) {
			$result = array('type'=>'error', 'msg'=>getMLText("access_denied"));
			echo json_encode($result);
			exit;
		}
	} else {
		$result = array('type'=>'error', 'msg'=>getMLText("access_denied"));
		echo json_encode($result);
		exit;
	}

	if (isset($_GET["acoid"])) {
		if(!($aco = SeedDMS_Aco::getInstance((int) $_GET["acoid"], $dms))) {
			$result = array('type'=>'error', 'msg'=>getMLText("access_denied"));
			echo json_encode($result);
			exit;
		}
	} else {
		$result = array('type'=>'error', 'msg'=>getMLText("access_denied"));
		echo json_encode($result);
		exit;
	}
}

switch($action) {
case "toggle_permission":
	$acl = new SeedDMS_Acl($dms);
	if($acl->toggle($aro, $aco))
		$result = array('type'=>'success', 'msg'=>getMLText('success_toogle_permission'));
	else
		$result = array('type'=>'error', 'msg'=>getMLText('error_toogle_permission'));
	header('Content-Type: application/json');
	echo json_encode($result);
	break;
case "add_permission":
	$acl = new SeedDMS_Acl($dms);
	if($acl->add($aro, $aco))
		$result = array('type'=>'success', 'msg'=>getMLText('success_add_permission'));
	else
		$result = array('type'=>'error', 'msg'=>getMLText('error_add_permission'));
	header('Content-Type: application/json');
	echo json_encode($result);
	break;
case "remove_permission":
	$acl = new SeedDMS_Acl($dms);
	if($acl->remove($aro, $aco))
		$result = array('type'=>'success', 'msg'=>getMLText('success_remove_permission'));
	else
		$result = array('type'=>'error', 'msg'=>getMLText('error_remove_permission'));
	header('Content-Type: application/json');
	echo json_encode($result);
	break;
case "add_aro":
	if(SeedDMS_Aro::getInstance($role, $dms)) {
		$result = array('type'=>'success', 'msg'=>getMLText('success_add_aro'));
	} else {
		$result = array('type'=>'error', 'msg'=>getMLText('error_add_aro'));
	}
	header('Content-Type: application/json');
	echo json_encode($result);
	break;
}

