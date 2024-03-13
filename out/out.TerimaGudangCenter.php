<?php


if(!isset($settings))
	require_once("../inc/inc.Settings.php");
require_once("inc/inc.LogInit.php");
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
	UI::exitError(getMLText("my_documents"),getMLText("access_denied"));
}

if ($user->isGuest()) {
	UI::exitError(getMLText("my_documents"),getMLText("access_denied"));
}

// Check to see if the user wants to see only those documents that are still
// in the review / approve stages.
$listtype = '';
if (isset($_GET["list"])) {
	$listtype = $_GET['list'];
}

$orderby='n';
if (isset($_GET["orderby"]) && strlen($_GET["orderby"])==1 ) {
	$orderby=$_GET["orderby"];
}
$orderdir='asc';
if (!empty($_GET["orderdir"])) {
	$orderdir=$_GET["orderdir"];
}

$allGudangcenter = $dms->getAllGudangCenter();
if (is_bool($allGudangcenter)) {
	UI::exitError(getMLText("admin_tools"),getMLText("internal_error"), false, $isajax);
}

if($view) {
	$view->setParam('showtree', showtree());
	$view->setParam('orderby', $orderby);
	$view->setParam('orderdir', $orderdir);
	$view->setParam('showtree', showtree());
	$view->setParam('listtype', $listtype);
	$view->setParam('workflowmode', $settings->_workflowMode);
	$view->setParam('cachedir', $settings->_cacheDir);
	$view->setParam('previewWidthList', $settings->_previewWidthList);
	$view->setParam('previewConverters', isset($settings->_converters['preview']) ? $settings->_converters['preview'] : array());
	$view->setParam('timeout', $settings->_cmdTimeout);
	$view->setParam('accessobject', $accessop);
	$view->setParam('xsendfile', $settings->_enableXsendfile);
	$view->setParam('onepage', $settings->_onePageMode); 
	$view->setParam('allgudangcenter', $allGudangcenter);
	$view($_GET);
	exit;
}
