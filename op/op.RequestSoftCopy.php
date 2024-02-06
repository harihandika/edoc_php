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
include("../inc/inc.Authentication.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassController.php");

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$controller = Controller::factory($tmp[1], array('dms'=>$dms, 'user'=>$user));

/* Check if the form data comes from a trusted request */
if(!checkFormKey('requestsoftcopy')) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_request_token"))),getMLText("invalid_request_token"));
}

if (!isset($_POST["folderid"]) || !is_numeric($_POST["folderid"]) || intval($_POST["folderid"])<1) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

$folderid = $_POST["folderid"];
$folder = $dms->getFolder($folderid);

if (!is_object($folder)) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

$folderPathHTML = getFolderPathHTML($folder, true);

if ($folder->getAccessMode($user, 'folder') < M_READWRITE) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
}

if($settings->_quota > 0) {
	$remain = checkQuota($user);
	if ($remain < 0) {
		UI::exitError(getMLText("folder_title", array("foldername" => htmlspecialchars($folder->getName()))),getMLText("quota_exceeded", array('bytes'=>SeedDMS_Core_File::format_filesize(abs($remain)))));
	}
}

$accessop = new SeedDMS_AccessOperation($dms, $user, $settings);
if ($accessop->check_controller_access($controller, array('action'=>'setOwner'))) {
	$ownerid = (int) $_POST["ownerid"];
	if($ownerid) {
		if(!($owner = $dms->getUser($ownerid))) {
			UI::exitError(getMLText("folder_title", array("foldername" => $requestsoftcopy->getName())),getMLText("error_occured"));
		}
	} else {
		$owner = $user;
	}
} else {
	$owner = $user;
}
$documentid = $_POST["documentid"];
$keterangan = $_POST["keterangan"];
$keperluan = $_POST["keperluan"];

$documents = $dms->getAllDocuments();
foreach($documents as $document) {
	if($document->getID() == $documentid)
	$names = array(htmlspecialchars($document->getName()));
}
$name = $names[0];

// $document = $dms->getDocument($documentid);
// if($document){
// 	$name = $document->getName();
// }else{
// 	$name = $documentid;
// };

$expires = makeTsFromDate($_POST["expdate"]);

$cats = array();

if(isset($_POST["attributes"]))
	$attributes = $_POST["attributes"];
else
	$attributes = array();

if(isset($_POST["attributes_version"]))
	$attributes_version = $_POST["attributes_version"];
else
	$attributes_version = array();

$reqversion = !empty($_POST['reqversion']) ? (int)$_POST["reqversion"] : 0;
if ($reqversion<1) $reqversion=1;

// Get the list of reviewers and approvers for this document.
$reviewers = array();
$approvers = array();
$recipients = array();
$reviewers["i"] = array();
$reviewers["g"] = array();
$approvers["i"] = array();
$approvers["g"] = array();
$recipients["i"] = array();
$recipients["g"] = array();
$workflow = null;

if($settings->_workflowMode == 'traditional' || $settings->_workflowMode == 'traditional_only_approval') {
	if($settings->_workflowMode == 'traditional') {
		// Retrieve the list of individual reviewers from the form.
		if (isset($_POST["indReviewers"])) {
			foreach ($_POST["indReviewers"] as $ind) {
				$reviewers["i"][] = $ind;
			}
		}
		// Retrieve the list of reviewer groups from the form.
		if (isset($_POST["grpReviewers"])) {
			foreach ($_POST["grpReviewers"] as $grp) {
				$reviewers["g"][] = $grp;
			}
		}
		// Retrieve the list of reviewer groups whose members become individual reviewers
		if (isset($_POST["grpIndReviewers"])) {
			foreach ($_POST["grpIndReviewers"] as $grp) {
				if($group = $dms->getGroup($grp)) {
					$members = $group->getUsers();
					foreach($members as $member)
						$reviewers["i"][] = $member->getID();
				}
			}
		}
	}
	
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

	// Retrieve the list of individual approvers from the form.
	if (isset($_POST["indApprovers"])) {
		foreach ($_POST["indApprovers"] as $ind) {
			$approvers["i"][] = $ind;
		}
	}
	// Retrieve the list of approver groups from the form.
	if (isset($_POST["grpApprovers"])) {
		foreach ($_POST["grpApprovers"] as $grp) {
			$approvers["g"][] = $grp;
		}
	}
	// Retrieve the list of reviewer groups whose members become individual approvers
	if (isset($_POST["grpIndApprovers"])) {
		foreach ($_POST["grpIndApprovers"] as $grp) {
			if($group = $dms->getGroup($grp)) {
				$members = $group->getUsers();
				foreach($members as $member)
					$approvers["i"][] = $member->getID();
			}
		}
	}

	// add mandatory reviewers/approvers
	if($settings->_workflowMode == 'traditional') {
		$mreviewers = getMandatoryReviewers($folder, $user);
		if($mreviewers['i'])
			$reviewers['i'] = array_merge($reviewers['i'], $mreviewers['i']);
	}
	$mapprovers = getMandatoryApprovers($folder, $user);
	if($mapprovers['i'])
		$approvers['i'] = array_merge($approvers['i'], $mapprovers['i']);

	if($settings->_workflowMode == 'traditional' && !$settings->_allowReviewerOnly) {
		/* Check if reviewers are send but no approvers */
		if(($reviewers["i"] || $reviewers["g"]) && !$approvers["i"] && !$approvers["g"]) {
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("error_uploading_reviewer_only"));
		}
	}
} elseif($settings->_workflowMode == 'advanced') {
	if(!$workflows = $user->getMandatoryWorkflows()) {
		if(isset($_POST["workflow"]))
			$workflow = $dms->getWorkflow($_POST["workflow"]);
		else
			$workflow = null;
	} else {
		/* If there is excactly 1 mandatory workflow, then set no matter what has
		 * been posted in 'workflow', otherwise check if the posted workflow is in the
		 * list of mandatory workflows. If not, then take the first one.
		 */
		$workflow = array_shift($workflows);
		foreach($workflows as $mw)
			if($mw->getID() == $_POST['workflow']) {$workflow = $mw; break;}
	}
}

// Retrieve the list of individual recipients from the form.
$recipients["i"] = array();
if (isset($_POST["indRecipients"])) {
	foreach ($_POST["indRecipients"] as $ind) {
		$recipients["i"][] = $ind;
	}
}
// Retrieve the list of recipient groups from the form.
$recipients["g"] = array();
if (isset($_POST["grpRecipients"])) {
	foreach ($_POST["grpRecipients"] as $grp) {
		$recipients["g"][] = $grp;
	}
}
// Retrieve the list of recipient groups whose members become individual recipients
if (isset($_POST["grpIndRecipients"])) {
	foreach ($_POST["grpIndRecipients"] as $grp) {
		if($group = $dms->getGroup($grp)) {
			$members = $group->getUsers();
			foreach($members as $member) {
				/* Do not add the uploader itself and reviewers */
				if(!$settings->_enableFilterReceipt || ($member->getID() != $user->getID() && !in_array($member->getID(), $reviewers['i'])))
					if(!in_array($member->getID(), $recipients["i"]))
						$recipients["i"][] = $member->getID();
			}
		}
	}
}

$docsource = 'upload';

	if($settings->_overrideMimeType) {
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$tmpfiletype = finfo_file($finfo, $userfiletmp);
		if($tmpfiletype != 'application/octet-stream')
			$userfiletype = $tmpfiletype;
	}

	if(!$settings->_enableDuplicateDocNames) {
		if($requestsoftcopy->hasDocumentByName($name)) {
			UI::exitError(getMLText("folder_title", array("foldername" => $requestsoftcopy->getName())),getMLText("document_duplicate_name"));
		}
	}

	$controller->setParam('documentsource', $docsource);
	$controller->setParam('folder', $folder);
	$controller->setParam('fulltextservice', $fulltextservice);
	$controller->setParam('documentid', $documentid);
	$controller->setParam('keterangan', $keterangan);
	$controller->setParam('keperluan', $keperluan);
	$controller->setParam('expires', $expires);
	// $controller->setParam('status', $status);
	$controller->setParam('categories', $cats);
	$controller->setParam('owner', $owner);
	$controller->setParam('reviewers', $reviewers);
	$controller->setParam('approvers', $approvers);
	$controller->setParam('recipients', $recipients);
	$controller->setParam('reqversion', $reqversion);
	$controller->setParam('attributes', $attributes);
	$controller->setParam('attributesversion', $attributes_version);
	$controller->setParam('workflow', $workflow);
	$controller->setParam('notificationgroups', $notgroups);
	$controller->setParam('notificationusers', $notusers);
	$controller->setParam('initialdocumentstatus', $settings->_initialDocumentStatus);
	$controller->setParam('maxsizeforfulltext', $settings->_maxSizeForFullText);
	$controller->setParam('defaultaccessdocs', $settings->_defaultAccessDocs);

	if(!$requestsoftcopy = $controller->run()) {
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText($controller->getErrorMsg()));
	} else {
		// Send notification to subscribers.
		if($notifier) {
			$fnl = $folder->getNotifyList();
			$snl = $requestsoftcopy->getNotifyList();
			$nl = array(
				'users'=>array_unique(array_merge($snl['users'], $fnl['users']), SORT_REGULAR),
				'groups'=>array_unique(array_merge($snl['groups'], $fnl['groups']), SORT_REGULAR)
			);
			$subject = "new_document_email_subject";
			$message = "new_document_email_body";
			$params = array();
			$params['name'] = $name;
			$params['category'] = $folder->getName();
			$params['folder_path'] = $folder->getFolderPathPlain();
			$params['projectname'] = $folder->getCodeProject($folder->getID());	
			$params['status'] = $settings->_initialDocumentStatus;
			$params['assignnotif'] = $notusers!=NULL?$notusers[0]->getFullName():'';
			$params['username'] = $user->getFullName();
			$params['recipient'] = $dms->getUser($recipients)!=NULL?$dms->getUser($recipients)->getFullName():'';
			// $params['comment'] = $comment;
			// $params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
			$params['sitename'] = $settings->_siteName;
			$params['http_root'] = $settings->_httpRoot;
			$notifier->toList($user, $nl["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
			foreach ($nl["groups"] as $grp) {
				$notifier->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
			}

			/* Get workflow from controller in case it was modified in a hook */
			$workflow = $controller->getParam('workflow');
			if($workflow && $settings->_enableNotificationWorkflow) {
				$subject = "request_workflow_action_email_subject";
				$message = "request_workflow_action_email_body";
				$params = array();
				$params['name'] = $requestsoftcopy->getName();
				$params['version'] = $reqversion;
				$params['projectname'] = $folder->getCodeProject($folder->getID());
				$params['category'] = $folder->getName();
				$params['status'] = $settings->_initialDocumentStatus;
				$params['workflow'] = $workflow->getName();
				$params['folder_path'] = $folder->getFolderPathPlain();
				$params['current_state'] = $workflow->getInitState()->getName();
				$params['username'] = $user->getFullName();
				$params['assignnotif'] = $notusers!=NULL?$notusers[0]->getFullName():'';
				$params['review'] = $dms->getUser($reviewers)!=NULL?$dms->getUser($reviewers["i"][0])->getFullName():'';
				$params['approve'] = $dms->getUser($approvers)!=NULL?$dms->getUser($approvers["i"][0])->getFullName():'';
				$params['recipient'] = $dms->getUser($recipients)!=NULL?$dms->getUser($recipients)->getFullName():'';
				$params['sitename'] = $settings->_siteName;
				$params['http_root'] = $settings->_httpRoot;
				// $params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();

				foreach($workflow->getNextTransitions($workflow->getInitState()) as $ntransition) {
					foreach($ntransition->getUsers() as $tuser) {
						$notifier->toIndividual($user, $tuser->getUser(), $subject, $message, $params, SeedDMS_NotificationService::RECV_WORKFLOW, $medda);
					}
					foreach($ntransition->getGroups() as $tuser) {
						$notifier->toGroup($user, $tuser->getGroup(), $subject, $message, $params, SeedDMS_NotificationService::RECV_WORKFLOW);
					}
				}
			}

			// if($settings->_enableNotificationAppRev) {

			// 	$reviewers = $controller->getParam('reviewers');
        	// 	$approvers = $controller->getParam('approvers');
			// 	if($reviewers['i'] || $reviewers['g']) {
			// 		$subject = "review_request_email_subject";
			// 		$message = "review_request_email_body";
			// 		$params = array();
			// 		$params['name'] = $requestsoftcopy->getName();
			// 		$params['folder_path'] = $folder->getFolderPathPlain();
			// 		$params['projectname'] = $folder->getCodeProject($folder->getID());
			// 		$params['category'] = $folder->getName();
			// 		$params['status'] = $settings->_initialDocumentStatus;
			// 		$params['version'] = $reqversion;
			// 		$params['keterangan'] = $keterangan;
			// 		$params['keperluan'] = $keperluan;
			// 		// $params['status'] = $status;
			// 		$params['username'] = $user->getFullName();
			// 		$params['review'] = $dms->getUser($reviewers)!=NULL?$dms->getUser($reviewers["i"][0])->getFullName():'';
			// 		$params['approve'] = $dms->getUser($approvers)!=NULL?$dms->getUser($approvers["i"][0])->getFullName():'';
			// 		$params['recipient'] = $dms->getUser($recipients)!=NULL?$dms->getUser($recipients)->getFullName():'';
			// 		$params['assignnotif'] = $notusers!=NULL?$notusers[0]->getFullName():'';
			// 		$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
			// 		$params['sitename'] = $settings->_siteName;
			// 		$params['http_root'] = $settings->_httpRoot;

			// 		foreach($reviewers['i'] as $reviewerid) {
			// 			$notifier->toIndividual($user, $dms->getUser($reviewerid), $subject, $message, $params, SeedDMS_NotificationService::RECV_REVIEWER);
			// 		}
			// 		foreach($reviewers['g'] as $reviewergrpid) {
			// 			$notifier->toGroup($user, $dms->getGroup($reviewergrpid), $subject, $message, $params, SeedDMS_NotificationService::RECV_REVIEWER);
			// 		}
			// 	}

			// 	elseif($approvers['i'] || $approvers['g']) {
			// 		$subject = "approval_request_email_subject";
			// 		$message = "approval_request_email_body";
			// 		$params = array();
			// 		$params['name'] = $requestsoftcopy->getName();
			// 		$params['folder_path'] = $folder->getFolderPathPlain();
			// 		$params['projectname'] = $folder->getCodeProject($folder->getID());
			// 		$params['category'] = $folder->getName();
			// 		$params['status'] = $settings->_initialDocumentStatus;
			// 		$params['version'] = $reqversion;
			// 		$params['keterangan'] = $keterangan;
			// 		$params['keperluan'] = $keperluan;
			// 		// $params['status'] = $status;
			// 		$params['username'] = $user->getFullName();
			// 		$params['review'] = $dms->getUser($reviewers)!=NULL?'':'';
			// 		$params['approve'] = $dms->getUser($approvers)!=NULL?$dms->getUser($approvers["i"][0])->getFullName():'';
			// 		var_dump($params['approve']);
			// 		die();
			// 		$params['recipient'] = $dms->getUser($recipients)!=NULL?$dms->getUser($recipients)->getFullName():'';
			// 		$params['assignnotif'] = $notusers!=NULL?$notusers[0]->getFullName():'';
			// 		$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
			// 		$params['sitename'] = $settings->_siteName;
			// 		$params['http_root'] = $settings->_httpRoot;

			// 		foreach($approvers['i'] as $approverid) {
			// 			$notifier->toIndividual($user, $dms->getUser($approverid), $subject, $message, $params, SeedDMS_NotificationService::RECV_APPROVER);
			// 		}
			// 		foreach($approvers['g'] as $approvergrpid) {
			// 			$notifier->toGroup($user, $dms->getGroup($approvergrpid), $subject, $message, $params, SeedDMS_NotificationService::RECV_APPROVER);
			// 		}
			// 	}
			// }
		}
		if($settings->_removeFromDropFolder) {
			if(file_exists($userfiletmp)) {
				unlink($userfiletmp);
			}
		}
	}
	
	add_log_line("?name=".$name."&requestsoftcopyid=".$requestsoftcopyid);
	// }

header("Location:../out/out.ViewFolder.php?requestsoftcopyid=".$requestsoftcopyid."&showtree=".$_POST["showtree"]);
