<?php

if(!isset($settings))
	require_once("../inc/inc.Settings.php");
require_once("inc/inc.LogInit.php");
require_once("inc/inc.Utils.php");
require_once("inc/inc.Language.php");
require_once("inc/inc.Init.php");
require_once("inc/inc.Extension.php");
require_once("inc/inc.DBInit.php");
require_once("inc/inc.ClassUI.php");
require_once("inc/inc.Authentication.php");

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$view = UI::factory($theme, $tmp[1], array('dms'=>$dms, 'user'=>$user));
$accessop = new SeedDMS_AccessOperation($dms, $user, $settings);
if (!$accessop->check_view_access($view, $_GET)) {
	UI::exitError(getMLText("gudangcenter_title", array("gudangcentername" => '')),getMLText("access_denied"));
}

if (!isset($_GET["folderid"]) || !is_numeric($_GET["folderid"]) || intval($_GET["folderid"])<1) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}
$folderid = $_GET["folderid"];
$folder = $dms->getFolder($_GET["folderid"]);
if (!is_object($folder)) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

if ($folder->getAccessMode($user) < M_READWRITE) {
	UI::exitError(getMLText("folder_title", array("foldername" => htmlspecialchars($folder->getName()))),getMLText("access_denied"));
}

$worklocations = $dms->getAllWorkLocations();
if (is_bool($worklocations)) {
	UI::exitError(getMLText("admin_tools"),getMLText("internal_error"), false, $isajax);
}

$documents = $dms->getAllDocuments();
if (is_bool($documents)) {
	UI::exitError(getMLText("admin_tools"),getMLText("internal_error"), false, $isajax);
}

if($view) {
	$view->setParam('folder', $folder);
	$view->setParam('strictformcheck', $settings->_strictFormCheck);
	$view->setParam('defaultposition', $settings->_defaultDocPosition);
	$view->setParam('orderby', $settings->_sortFoldersDefault);
	$view->setParam('accessobject', $accessop);
	$view->setParam('allworklocations', $worklocations);
	$view->setParam('documents', $documents);
	$view->setParam('sortusersinlist', $settings->_sortUsersInList);
	$view($_GET);
	exit;
}
