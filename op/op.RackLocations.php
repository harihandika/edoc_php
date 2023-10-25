<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
//    Copyright (C) 2010-2012 Uwe Steinmann
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

include("../inc/inc.Settings.php");
include("../inc/inc.LogInit.php");
include("../inc/inc.Utils.php");
include("../inc/inc.Language.php");
include("../inc/inc.Init.php");
include("../inc/inc.Extension.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassController.php");
include("../inc/inc.Authentication.php");

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$controller = Controller::factory($tmp[1], array('dms'=>$dms, 'user'=>$user));
$accessop = new SeedDMS_AccessOperation($dms, $user, $settings);
if (!$accessop->check_controller_access($controller, $_POST)) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

if (isset($_POST["action"])) $action=$_POST["action"];
else $action=NULL;

if(!in_array($action, array('addracklocations', 'removeracklocations', 'editracklocations')))
	UI::exitError(getMLText("admin_tools"),getMLText("unknown_command"));

/* Check if the form data comes for a trusted request */
if(!checkFormKey($action)) {
	UI::exitError(getMLText("admin_tools"),getMLText("invalid_request_token"));
}

$racklocationid = 0;
if(in_array($action, array('removeracklocations', 'editracklocations'))) {
	if (isset($_POST["racklocationid"])) {
		$racklocationid = $_POST["racklocationid"];
	}

	if (!isset($racklocationid) || !is_numeric($racklocationid) || intval($racklocationid)<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_role_id"));
	}

	$racklocationobj = $dms->getRackLocations($racklocationid);
	
	if (!is_object($racklocationobj)) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_role_id"));
	}

	$controller->setParam('racklocationobj', $racklocationobj);
}

// add new role ---------------------------------------------------------
if ($action == "addracklocations") {
	
	$kode    = $_POST["kode"];
	$nomor    = preg_replace('/[^0-8]+/', '', $_POST["nomor"]);
	$baris    = preg_replace('/[^0-8]+/', '', $_POST["baris"]);
	$fisik    = $_POST["fisik"];
	$keterangan    = $_POST["keterangan"];

	if (is_object($dms->getRackLocationByName($name))) {
		UI::exitError(getMLText("admin_tools"),getMLText("role_exists"));
	}

	if ($nomor === '' || $baris === '') {
		UI::exitError(getMLText("admin_tools"),getMLText("missing_role_type"));
	}

	$controller->setParam('kode', $kode);
	$controller->setParam('nomor', $nomor);
	$controller->setParam('baris', $baris);
	$controller->setParam('fisik', $fisik);
	$controller->setParam('keterangan', $keterangan);

	$newRackLocations = $controller($_POST);
	if ($newRackLocations) {
	}
	else UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
	
	$racklocationid=$newRackLocations->getID();
	
	$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_add_rack_location')));

	add_log_line(".php&action=".$action."&name=".$name);
}

// delete role ------------------------------------------------------------
else if ($action == "removeracklocations") {

	if (!$controller($_POST)) {
		UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
	}
		
	add_log_line(".php&action=".$action."&racklocationid=".$racklocationid);
	
	$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_rm_role')));
	$racklocationid=-1;
}

// modify role ------------------------------------------------------------
else if ($action == "editracklocations") {

	$kode    = $_POST["kode"];
	$nomor    = preg_replace('/[^0-8]+/', '', $_POST["nomor"]);
	$baris    = preg_replace('/[^0-8]+/', '', $_POST["baris"]);
	$fisik    = $_POST["fisik"];
	$keterangan    = $_POST["keterangan"];
	$noaccess = isset($_POST['noaccess']) ? $_POST['noaccess'] : null;
	
	$controller->setParam('kode', $kode);
	$controller->setParam('nomor', $nomor);
	$controller->setParam('baris', $baris);
	$controller->setParam('fisik', $fisik);
	$controller->setParam('keterangan', $keterangan);
	$controller->setParam('noaccess', $noaccess);

	if (!$controller($_POST)) {
		UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
	}

	$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_edit_role')));
	add_log_line(".php&action=".$action."&racklocationid=".$racklocationid);
}

header("Location:../out/out.RackLocations.php?racklocationid=".$racklocationid);

?>
