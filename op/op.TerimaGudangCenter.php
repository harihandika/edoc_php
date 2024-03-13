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
	
if ($_GET["type"]=="gudangcenter"){

	if ($_GET["action"]=="add"){
		if (!isset($_POST["gudangcenterid"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$gudangcenterid = $_POST["gudangcenterid"];

	}else if ($_GET["action"]=="del"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$gudangcenterid = $_GET["id"];

	}else if($_GET["action"]=="app"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$gudangcenterid = $_GET["id"];

	}else if($_GET["action"]=="rej"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$gudangcenterid = $_GET["id"];

	}else if ($_GET["action"]=="rec"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$gudangcenterid = $_GET["id"];

	}else if ($_GET["action"]=="dec"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$gudangcenterid = $_GET["id"];

	}else if ($_GET["action"]=="return"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$gudangcenterid = $_GET["id"];

	}else if ($_GET["action"]=="done"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$gudangcenterid = $_GET["id"];

	}else UI::exitError(getMLText("my_account"),getMLText("error_occured"));

	if(!$gudangcenterid || !($gudangcenter = $dms->getGudangCenter($gudangcenterid))) {
		UI::exitError(getMLText("my_account"),getMLText("error_no_document_selected"));
	}
	
	// if ($document->getAccessMode($user) < M_READ) 
	// 	UI::exitError(getMLText("my_account"),getMLText("error_occured"));

	if ($_GET["action"]=="add") $gudangcenter->addNotify($userid, true);
	else if ($_GET["action"]=="del") $gudangcenter->removeNotify($userid, true);
	else if ($_GET["action"]=="app") $gudangcenter->approveNotify($userid, true);
	else if ($_GET["action"]=="rej")$gudangcenter->rejectNotify($userid, true);
	else if ($_GET["action"]=="rec")$gudangcenter->receiveNotify($userid, true);
	else if ($_GET["action"]=="dec") $gudangcenter->declineNotify($userid, true);
	else if ($_GET["action"]=="return") $gudangcenter->expiredNotify($userid, true);
	else if ($_GET["action"]=="done") $gudangcenter->finishNotify($userid, true);

}

header("Location:../out/out.RequestTaskHard.php");

?>
