<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
//    Copyright (C) 2010-2106 Uwe Steinmann
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


// if (!isset($_POST["softcopyid"]) || !is_numeric($_POST["softcopyid"]) || intval($_POST["softcopyid"])<1) {
// 	UI::exitError(getMLText("admin_tools"),getMLText("invalid_groupsss_id"));
// 	}
if (isset($_GET["softcopyid"])) {
	if (!is_numeric($_GET["softcopyid"])) {
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("unknown_user"));
		$name = $_POST["name"];
$keterangan = $_POST["keterangan"];
$keperluan = $_POST["keperluan"];
	}
	if (!strcasecmp($action, "addaccess") && $_GET["softcopyid"]==-1) {
		$softcopyid = -1;
	}
	else {
		if (!is_object($dms->getUser($_GET["softcopyid"]))) {
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("unknown_user"));
		}
		$softcopyid = $_GET["softcopyid"];
	}
}

// $softcopyid = $_POST["softcopyid"];
// $softcopy = $dms->getSoftCopy($softcopyid);

$name = $_POST["name"];
$keterangan = $_POST["keterangan"];
$keperluan = $_POST["keperluan"];
// if(isset($_POST["attributes"]))
// 	$attributes = $_POST["attributes"];
// else
// 	$attributes = array();
/*
foreach($attributes as $attrdefid=>$attribute) {
	$attrdef = $dms->getAttributeDefinition($attrdefid);
	if($attribute) {
		if(!$attrdef->validate($attribute)) {
			$errmsg = getAttributeValidationText($attrdef->getValidationError(), $attrdef->getName(), $attribute);
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())), $errmsg);
		}
	} elseif($attrdef->getMinValues() > 0) {
		UI::exitError(getMLText("folder_title", array("foldername" => $document->getName())),getMLText("attr_min_values", array("attrname"=>$attrdef->getName())));
	}
}
 */

/* Check if additional notification shall be added */
$notusers = array();
if(!empty($_POST['notification_users'])) {
	foreach($_POST['notification_users'] as $notuserid) {
		$notuser = $dms->getUser($notuserid);
		if($notuser) {
			$notusers[] = $notuser;
		}
	}
}

$notgroups = array();
if(!empty($_POST['notification_groups'])) {
	foreach($_POST['notification_groups'] as $notgroupid) {
		$notgroup = $dms->getGroup($notgroupid);
		if($notgroup) {
			$notgroups[] = $notgroup;
		}
	}
}

/* Check if name already exists in the folder */
// if(!$settings->_enableDuplicateSubFolderNames) {
// 	if($folder->hasSubFolderByName($name)) {
// 		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("subfolder_duplicate_name"));
// 	}
// }

$controller->setParam('fulltextservice', $fulltextservice);
// $controller->setParam('folder', $folder);
// $controller->setParam('softcopy', $softcopy);
$controller->setParam('name', $name);
$controller->setParam('keterangan', $keterangan);
$controller->setParam('keperluan', $keperluan);
// $controller->setParam('attributes', $attributes);
$controller->setParam('notificationgroups', $notgroups);
$controller->setParam('notificationusers', $notusers);
// if(!$subSoftCopy = $controller->run()) {
// 	UI::exitError(getMLText("softcopy_title", array("softcopyname" => $softcopy->getName())),getMLText($controller->getErrorMsg()));
// } else {
// 	// Send notification to subscribers.
// 	if($notifier) {
// 		$fnl = $softcopy->getNotifyList();
// 		$snl = $subSoftCopy->getNotifyList();
// 		$nl = array(
// 			'users'=>array_unique(array_merge($snl['users'], $fnl['users']), SORT_REGULAR),
// 			'groups'=>array_unique(array_merge($snl['groups'], $fnl['groups']), SORT_REGULAR)
// 		);

// 		$subject = "new_subsoftcopy_email_subject";
// 		$message = "new_subsoftcopy_email_body";
// 		$params = array();
// 		$params['name'] = $subSoftCopy->getName();
// 		$params['softcopy_name'] = $softcopy->getName();
// 		$params['folder_path'] = $folder->getFolderPathPlain();
// 		$params['username'] = $user->getFullName();
// 		$params['keterangan'] = $keterangan;
// 		$params['keperluan'] = $keperluan;
// 		$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewFolder.php?softcopyid=".$subSoftCopy->getID();
// 		$params['sitename'] = $settings->_siteName;
// 		$params['http_root'] = $settings->_httpRoot;
// 		$notifier->toList($user, $nl["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
// 		foreach ($nl["groups"] as $grp) {
// 			$notifier->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
// 		}
// 	}
// }

add_log_line("?name=".$name."&softcopyid=".$softcopyid);

header("Location:../out/out.ViewFolder.php?softcopyid=".$softcopyid."&showtree=".$_POST["showtree"]);

?>
