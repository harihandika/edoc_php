<?php

include("../inc/inc.Settings.php");
include("../inc/inc.LogInit.php");
include("../inc/inc.Init.php");
include("../inc/inc.Extension.php");
include("../inc/inc.Language.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if ($user->isGuest()) {
	UI::exitError(getMLText("my_account"),getMLText("access_denied"));
}
	
if ($_GET["type"]=="requestsoftcopy"){

	if ($_GET["action"]=="add"){
		if (!isset($_POST["requestsoftcopyid"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$requestsoftcopyid = $_POST["requestsoftcopyid"];

	}else if ($_GET["action"]=="del"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$requestsoftcopyid = $_GET["id"];

	}else if($_GET["action"]=="app"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$requestsoftcopyid = $_GET["id"];

	}else if($_GET["action"]=="rej"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$requestsoftcopyid = $_GET["id"];

	}else if ($_GET["action"]=="rec"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$requestsoftcopyid = $_GET["id"];

	}else if ($_GET["action"]=="dec"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$requestsoftcopyid = $_GET["id"];

	}else if ($_GET["action"]=="ex"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$requestsoftcopyid = $_GET["id"];

	}else UI::exitError(getMLText("my_account"),getMLText("error_occured"));

	if(!$requestsoftcopyid || !($requestsoftcopy = $dms->getRequestSoftCopy($requestsoftcopyid))) {
		UI::exitError(getMLText("my_account"),getMLText("error_no_document_selected"));
	}
	
	// if ($document->getAccessMode($user) < M_READ) 
	// 	UI::exitError(getMLText("my_account"),getMLText("error_occured"));

	if ($_GET["action"]=="add") $requestsoftcopy->addNotify($userid, true);
	else if ($_GET["action"]=="del") $requestsoftcopy->removeNotify($userid, true);
	else if ($_GET["action"]=="app") $requestsoftcopy->approveNotify($userid, true);
	else if ($_GET["action"]=="rej")$requestsoftcopy->rejectNotify($userid, true);
	else if ($_GET["action"]=="rec")$requestsoftcopy->receiveNotify($userid, true);
	else if ($_GET["action"]=="dec") $requestsoftcopy->declineNotify($userid, true);
	else if ($_GET["action"]=="ex") $requestsoftcopy->expiredNotify($userid, true);

}

header("Location:../out/out.Tasks.php");

?>
