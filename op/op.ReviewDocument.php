<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
//    Copyright (C) 2010-2016 Uwe Steinmann
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
include("../inc/inc.ClassAccessOperation.php");
include("../inc/inc.Authentication.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassController.php");

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$controller = Controller::factory($tmp[1], array('dms'=>$dms, 'user'=>$user));
$accessop = new SeedDMS_AccessOperation($dms, $user, $settings);

/* Check if the form data comes from a trusted request */
if(!checkFormKey('reviewdocument')) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_request_token"))),getMLText("invalid_request_token"));
}

if (!isset($_POST["documentid"]) || !is_numeric($_POST["documentid"]) || intval($_POST["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$documentid = $_POST["documentid"];
$document = $dms->getDocument($documentid);

if (!is_object($document)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

// verify if document may be reviewed
if (!$accessop->mayReview($document)){
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

$folder = $document->getFolder();

if (!isset($_POST["version"]) || !is_numeric($_POST["version"]) || intval($_POST["version"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

$version = $_POST["version"];
$content = $document->getContentByVersion($version);

if (!is_object($content)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

// operation is only allowed for the last document version
$latestContent = $document->getLatestContent();
if ($latestContent->getVersion()!=$version) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

$olddocstatus = $content->getStatus();

if (!isset($_POST["reviewStatus"]) || !is_numeric($_POST["reviewStatus"]) ||
		(intval($_POST["reviewStatus"])!=1 && intval($_POST["reviewStatus"])!=-1)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_review_status"));
}

if($_FILES["reviewfile"]["tmp_name"]) {
	if (is_uploaded_file($_FILES["reviewfile"]["tmp_name"]) && $_FILES['reviewfile']['error']!=0){
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("uploading_failed"));
	}
}

$controller->setParam('document', $document);
$controller->setParam('content', $content);
$controller->setParam('reviewstatus', $_POST["reviewStatus"]);
$controller->setParam('reviewtype', $_POST["reviewType"]);
if ($_POST["reviewType"] == "grp") {
	$group = $dms->getGroup($_POST['reviewGroup']);
} else {
	$group = null;
}
if($_FILES["reviewfile"]["tmp_name"])
	$file = $_FILES["reviewfile"]["tmp_name"];
else
	$file = '';
$controller->setParam('group', $group);
$controller->setParam('comment', $_POST["comment"]);
$controller->setParam('file', $file);
if(!$controller->run()) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText($controller->getErrorMsg()));
}

if ($_POST["reviewType"] == "ind" || $_POST["reviewType"] == "grp") {
	if($notifier) {
		$nl=$document->getNotifyList();
		$subject = "review_submit_email_subject";
		$message = "review_submit_email_body";
		$params = array();
		$params['name'] = $document->getName();
		$params['version'] = $version;
		$params['folder_path'] = $folder->getFolderPathPlain();
		$params['status'] = getReviewStatusText($_POST["reviewStatus"]);
		$params['comment'] = strip_tags($_POST['comment']);
		$params['username'] = $user->getFullName();
		$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID()."&currenttab=revapp";
		$params['sitename'] = $settings->_siteName;
		$params['http_root'] = $settings->_httpRoot;
		$notifier->toList($user, $nl["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		foreach ($nl["groups"] as $grp) {
			$notifier->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		}
//		$notifier->toIndividual($user, $content->getUser(), $subject, $message, $params, SeedDMS_NotificationService::RECV_OWNER);
	}
}

// var_dump($content);
// die();

/* Send notification about status change only if status has actually changed */
$newdocstatus = $content->getStatus();
if($olddocstatus['status'] != $newdocstatus['status']) {
	// Send notification to subscribers.
	if($notifier) {
		$nl=$document->getNotifyList();
		$subject = "document_status_changed_email_subject";
		$message = "document_status_changed_email_body";
		$params = array();
		$params['name'] = $document->getName();
		$params['folder_path'] = $folder->getFolderPathPlain();
		$params['status'] = getOverallStatusText($olddocstatus['status']).' → '.getReviewStatusText($newdocstatus['status']);
		$params['new_status_code'] = $newdocstatus['status'];
		$params['old_status_code'] = $olddocstatus['status'];
		$params['username'] = $user->getFullName();
		$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
		$params['sitename'] = $settings->_siteName;
		$params['http_root'] = $settings->_httpRoot;
		$notifier->toList($user, $nl["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		foreach ($nl["groups"] as $grp) {
			$notifier->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		}
//		$notifier->toIndividual($user, $content->getUser(), $subject, $message, $params, SeedDMS_NotificationService::RECV_OWNER);
	}
}

// Notify approvers, if necessary.
if ($newdocstatus['status'] == S_DRAFT_APP) {
	$approver = $dms->getUser($dastat["required"]);
	$requestUser = $document->getOwner();

	if($notifier) {
		if($docApprovalStatus = $content->getApprovalStatus()) {
			$subject = "approval_request_email_subject";
			$message = "approval_request_email_body";
			$params = array();
			$params['name'] = $document->getName();
			$params['folder_path'] = $folder->getFolderPathPlain();
			$params['version'] = $version;
			$params['username'] = $user->getFullName();
			$params['review'] = $dms->getUser($reviewers)!=NULL?$dms->getUser($reviewers["i"][0])->getFullName():'';
			$params['approve'] = $dms->getUser($approver);
			$params['projectname'] = $folder->getCodeProject($folder->getID());
			// var_dump($approver);
			// die();
			$params['recipient'] = $dms->getUser($recipients)!=NULL?$dms->getUser($recipients)->getFullName():'';
			$params['assignnotif'] = $notusers!=NULL?$notusers[0]->getFullName():'';
			$params['category'] = $folder->getName();
			$params['sitename'] = $settings->_siteName;
			$params['http_root'] = $settings->_httpRoot;
			$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID()."&currenttab=revapp";
			foreach ($docApprovalStatus as $dastat) {
			
				if ($dastat["status"] == 0) {
					if ($dastat["type"] == 0) {

						$approver = $dms->getUser($dastat["required"]);
						$notifier->toIndividual($document->getOwner(), $approver, $subject, $message, $params, SeedDMS_NotificationService::RECV_APPROVER);
					} elseif ($dastat["type"] == 1) {
					
						$group = $dms->getGroup($dastat["required"]);
						$notifier->toGroup($document->getOwner(), $group, $subject, $message, $params, SeedDMS_NotificationService::RECV_APPROVER);
					}
				}
			}
		}
	}
}

header("Location:../out/out.ViewDocument.php?documentid=".$documentid."&currenttab=revapp");

?>
