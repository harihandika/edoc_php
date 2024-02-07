<?php
/**
 * Implementation of MyDocuments view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
require_once("class.Bootstrap.php");

/**
 * Include class to preview documents
 */
require_once("SeedDMS/Preview.php");

/**
 * Class which outputs the html page for MyDocuments view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_StatusTasks extends SeedDMS_Bootstrap_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
?>
		$(document).ready(function(){
			$("#myInput").on("keyup", function() {
				var value = $(this).val().toLowerCase();
				$("#myTable tbody tr").filter(function() {
					$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
				});
			});
		});
<?php
	} /* }}} */

function statusRequestSoftCopy() { /* {{{ */

	$dms = $this->params['dms'];
	$user = $this->params['user'];
	$allRequestsoftcopy = $this->params['allrequestsoftcopy'];
	$showtree = $this->params['showtree'];
	$previewwidth = $this->params['previewWidthList'];
	?>
	<table id="myTable" class="table">
		<thead>
		<tr><th><?php printMLText('name'); ?></th><th><?php printMLText('keperluan');?></th><th><?php print('PIC'); ?></th><th><?php printMLText('from_date'); ?></th><th><?php printMLText('to_date'); ?></th><th><?php printMLText('status'); ?></th><th></th></tr>
		</thead>
		<tbody>
<?php

$documents = $dms->getAllDocuments();

$res=$user->getMandatoryApproverUser();
$approver = array();
$userapprover = array();
if($res) {
	foreach ($res as $r) {
			$s = $dms->getUser($r['userID']);
			$userapprover[] = htmlspecialchars($s->getID());
			$u = $dms->getUser($r['approverUserID']);
			$approver =  htmlspecialchars($u->getID());
	}
}

		foreach ($allRequestsoftcopy as $requestsoftcopy) {
			foreach ($requestsoftcopy->getOwner()->getPICNotifications(T_REQUESTSOFTCOPY) as $request){
				foreach ($documents as $document) {
					if($document->getID() == $requestsoftcopy->getDocumentID()){
				if ($request->getTarget() == $requestsoftcopy->getID() && $user->getID() == $requestsoftcopy->getOwner()->getID()){
			
			echo "<td>";

			$docID = $document->getID();
			$latestContent = $document->getLatestContent();
			$status = $requestsoftcopy->getStatus();
			$res = $user->getMandatoryApproverTasks($request->getUserID());
			$tmp = array();
			foreach ($res as $r) {
				if($r['approverUserID'] > 0) {
					$u = $dms->getUser($r['approverUserID']);
					$tmp[] =  htmlspecialchars($u->getFullName().' ('.$u->getLogin().')');
				}
			};
			if(!$tmp){
			$tmp = '';
			} else {
			foreach($tmp as $t){
			}
			}

		if($status == 2){
			echo "<img draggable=\"false\" class=\"mimeicon\" width=\"".$previewwidth."\" src=\"".$this->getMimeIcon($latestContent->getFileType())."\" ".($previewwidth ? "width=\"".$previewwidth."\"" : "")."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">". "      ".
			"<a draggable=\"false\" href=\"../out/out.ViewDocumentReq.php?documentid=".$docID."&showtree=".$showtree."\">" . htmlspecialchars($document->getName()) . "</a>"."<br />";
		}else{
		echo htmlspecialchars($document->getName()) ."<br />";
		}

			echo "<small>".htmlspecialchars($requestsoftcopy->getKeterangan())."</small>";
			echo "</td>";
			echo "<td>";
			echo "<small>".htmlspecialchars($requestsoftcopy->getKeperluan())."</small>";
			echo "</td>";
			echo "<td>";
			echo htmlspecialchars($user->getPICName($request->getUserID())->getFullName());
			echo "</td>";
			echo "<td>";
			echo htmlspecialchars(getLongReadableDate($requestsoftcopy->getDate()));
			echo "</td>";
			echo "<td>";
			echo htmlspecialchars(getLongReadableDate($requestsoftcopy->getExpires()));
			echo "</td>";
			echo "<td>";
			if($status == 0 ){
				echo "Waiting";
			} else if ($status == 1){
				echo "Waiting Approval By $t";
			} else if ($status == -1){
				echo "Decline by user";
			} else if ($status == 2){
				echo "Approve";
			} else if ($status == -2){
				echo "Reject";
			} else if ($status == 3){
				echo "Expired";
			}
			echo "</td>";
			echo "</tr>";
		}
		}
	}
	}  
	}
	?></table><?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$allRequestsoftcopy = $this->params['allrequestsoftcopy'];

		$this->htmlStartPage(getMLText("request_soft_copy"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");

		// $this->contentHeading(getMLText("request_soft_copy"));

	$this->contentHeading(getMLText("status"));

	// $this->requestSoftCopy();
	$this->statusRequestSoftCopy();

		$this->contentEnd();
		$this->htmlEndPage();
	} 
	
}
?>