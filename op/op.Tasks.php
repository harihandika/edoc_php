<?php
//    MyDMS. Document Management System
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

	}else UI::exitError(getMLText("my_account"),getMLText("error_occured"));

	if(!$requestsoftcopyid || !($requestsoftcopy = $dms->getRequestSoftCopy($requestsoftcopyid))) {
		UI::exitError(getMLText("my_account"),getMLText("error_no_document_selected"));
	}
	
	// if ($document->getAccessMode($user) < M_READ) 
	// 	UI::exitError(getMLText("my_account"),getMLText("error_occured"));

	if ($_GET["action"]=="add") $requestsoftcopy->addNotify($userid, true);
	else if ($_GET["action"]=="del") $requestsoftcopy->removeNotify($userid, true);
	else if ($_GET["action"]=="app") $requestsoftcopy->approveNotify($userid, true);
	else if ($_GET["action"]=="rej") $requestsoftcopy->rejectNotify($userid, true);
	else if ($_GET["action"]=="rec") $requestsoftcopy->receiveNotify($userid, true);
	else if ($_GET["action"]=="dec") $requestsoftcopy->declineNotify($userid, true);

}

header("Location:../out/out.ViewFolder.php");

?>
