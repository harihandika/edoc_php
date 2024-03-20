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
	
if ($_GET["type"]=="requesthardcopy"){

	if ($_GET["action"]=="add"){
		if (!isset($_POST["requesthardcopyid"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$requesthardcopyid = $_POST["requesthardcopyid"];

	}else if ($_GET["action"]=="del"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$requesthardcopyid = $_GET["id"];

	}else if($_GET["action"]=="app"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$requesthardcopyid = $_GET["id"];
		$ownerid = $_GET["ownerid"];
		$newuser = $dms->getUser($ownerid);

	}else if($_GET["action"]=="rej"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$requesthardcopyid = $_GET["id"];

	}else if ($_GET["action"]=="rec"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$requesthardcopyid = $_GET["id"];

	}else if ($_GET["action"]=="dec"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$requesthardcopyid = $_GET["id"];

	}else if ($_GET["action"]=="return"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$requesthardcopyid = $_GET["id"];

	}else if ($_GET["action"]=="done"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$requesthardcopyid = $_GET["id"];

	}else UI::exitError(getMLText("my_account"),getMLText("error_occured"));

	if(!$requesthardcopyid || !($requesthardcopy = $dms->getRequestHardCopy($requesthardcopyid))) {
		UI::exitError(getMLText("my_account"),getMLText("error_no_document_selected"));
	}
	
	// if ($document->getAccessMode($user) < M_READ) 
	// 	UI::exitError(getMLText("my_account"),getMLText("error_occured"));

	if ($_GET["action"]=="add") $requesthardcopy->addNotify();
	else if ($_GET["action"]=="del") $requesthardcopy->removeNotify();
	else if ($_GET["action"]=="app") {
		$requesthardcopy->approveNotify();
		$requesthardcopy->transferToUser($newuser);
	}
	else if ($_GET["action"]=="rej")$requesthardcopy->rejectNotify();
	else if ($_GET["action"]=="rec")$requesthardcopy->receiveNotify();
	else if ($_GET["action"]=="dec") $requesthardcopy->declineNotify();
	else if ($_GET["action"]=="return") $requesthardcopy->expiredNotify();
	else if ($_GET["action"]=="done") $requesthardcopy->finishNotify();

}

header("Location:../out/out.RequestTaskHard.php");

?>
