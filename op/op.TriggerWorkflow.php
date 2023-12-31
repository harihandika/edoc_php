<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
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
include("../inc/inc.Language.php");
include("../inc/inc.Init.php");
include("../inc/inc.Extension.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

/* Check if the form data comes from a trusted request */
if(!checkFormKey('triggerworkflow')) {
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

if (!isset($_POST["version"]) || !is_numeric($_POST["version"]) || intval($_POST["version"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

$version_num = $_POST["version"];
$version = $document->getContentByVersion($version_num);
if (!is_object($version)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

$workflow = $version->getWorkflow();
if (!is_object($workflow)) {
	UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("document_has_no_workflow"));
}

$transition = $dms->getWorkflowTransition($_POST["transition"]);
if (!is_object($transition)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_workflow_transition"));
}

if(!$version->triggerWorkflowTransitionIsAllowed($user, $transition)) {
	UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("access_denied"));
}

$workflow = $transition->getWorkflow();

if(isset($GLOBALS['SEEDDMS_HOOKS']['triggerWorkflowTransition'])) {
	foreach($GLOBALS['SEEDDMS_HOOKS']['triggerWorkflowTransition'] as $hookObj) {
		if (method_exists($hookObj, 'preTriggerWorkflowTransition')) {
			$hookObj->preTriggerWorkflowTransition(null, array('version'=>$version, 'transition'=>$transition, 'comment'=>$_POST["comment"]));
		}
	}
}

if($version->triggerWorkflowTransition($user, $transition, $_POST["comment"])) {
	if ($notifier) {
		$nl =	$document->getNotifyList();
		$folder = $document->getFolder();
		$subject = "transition_triggered_email_subject";
		$message = "transition_triggered_email_body";
		$params = array();
		$params['name'] = $document->getName();
		$params['version'] = $version->getVersion();
		$params['workflow'] = $workflow->getName();
		$params['action'] = $transition->getAction()->getName();
		$params['folder_path'] = $folder->getFolderPathPlain();
		$params['comment'] = $_POST["comment"];
		$params['previous_state'] = $transition->getState()->getName();
		$params['current_state'] = $transition->getNextState()->getName();
		$params['username'] = $user->getFullName();
		$params['sitename'] = $settings->_siteName;
		$params['http_root'] = $settings->_httpRoot;
		$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();

		// Send notification to subscribers.
		$notifier->toList($user, $nl["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		foreach ($nl["groups"] as $grp) {
			$notifier->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		}

		if($settings->_enableNotificationWorkflow) {
			$subject = "request_workflow_action_email_subject";
			$message = "request_workflow_action_email_body";
			$params = array();
			$params['name'] = $document->getName();
			$params['version'] = $version->getVersion();
			$params['workflow'] = $workflow->getName();
			$params['folder_path'] = $folder->getFolderPathPlain();
			$params['current_state'] = $transition->getNextState()->getName();
			$params['username'] = $user->getFullName();
			$params['sitename'] = $settings->_siteName;
			$params['http_root'] = $settings->_httpRoot;
			$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();

			$usersinformed = array();
			$groupsinformed = array();
			foreach($workflow->getNextTransitions($transition->getNextState()) as $ntransition) {
				foreach($ntransition->getUsers() as $tuser) {
					if(!in_array($tuser->getUser()->getID(), $usersinformed)) {
						$usersinformed[] = $tuser->getUser()->getID();
						$notifier->toIndividual($user, $tuser->getUser(), $subject, $message, $params, SeedDMS_NotificationService::RECV_WO▨KFLOW);
					}
				}
				foreach($ntransition->getGroups() as $tuser) {
					if(!in_array($tuser->getGroup()->getID(), $groupsinformed)) {
						$groupsinformed[] = $tuser->getGroup()->getID();
						$notifier->toGroup($user, $tuser->getGroup(), $subject, $message, $params, SeedDMS_NotificationService::RECV_WORKFLOW);
					}
				}
			}
		}
	}

	$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_trigger_workflow')));

	if(isset($GLOBALS['SEEDDMS_HOOKS']['triggerWorkflowTransition'])) {
		foreach($GLOBALS['SEEDDMS_HOOKS']['triggerWorkflowTransition'] as $hookObj) {
			if (method_exists($hookObj, 'postTriggerWorkflowTransition')) {
				$hookObj->postTriggerWorkflowTransition(null, array('version'=>$version, 'transition'=>$transition, 'comment'=>$_POST["comment"]));
			}
		}
	}
} else {
	$session->setSplashMsg(array('type'=>'error', 'msg'=>getMLText('error_trigger_workflow')));
}

add_log_line("?documentid=".$documentid."&version".$version_num);

header("Location:../out/out.ViewDocument.php?documentid=".$documentid."&currenttab=workflow");
?>
