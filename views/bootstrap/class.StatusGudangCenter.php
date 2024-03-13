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
class SeedDMS_View_StatusGudangCenter extends SeedDMS_Bootstrap_Style {

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

function statusGudangCenter() { /* {{{ */

	$dms = $this->params['dms'];
	$user = $this->params['user'];
	$allGudangcenter = $this->params['allgudangcenter'];
	$showtree = $this->params['showtree'];
	$previewwidth = $this->params['previewWidthList'];
	?>
	<table id="myTable" class="table">
		<thead>
		<tr><th><?php printMLText('name'); ?></th><th><?php printMLText('origin');?></th><th><?php printMLText('destiny');?></th><th><?php printMLText('location');?></th><th><?php print('PIC'); ?></th><th><?php printMLText('from_date'); ?></th><th><?php printMLText('to_date'); ?></th><th><?php printMLText('status'); ?></th><th></th></tr>
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

		foreach ($allGudangcenter as $gudangcenter) {
			foreach ($gudangcenter->getOwner()->getPICNotificationGudangCenter(T_GUDANGCENTER) as $request){
				foreach ($documents as $document) {
					if($document->getID() == $gudangcenter->getDocumentID()){
				if ($request->getTarget() == $gudangcenter->getID() && $user->getID() == $gudangcenter->getOwner()->getID()){
			
			echo "<td>";

			$docID = $document->getID();
			$latestContent = $document->getLatestContent();
			$status = $gudangcenter->getStatus();
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

			echo "<small>".htmlspecialchars($gudangcenter->getKeterangan())."</small>";
			echo "</td>";
			echo "<td>";
			echo "<small>".htmlspecialchars($gudangcenter->getOrigin())."</small>";
			echo "</td>";
			echo "<td>";
			echo "<small>".htmlspecialchars($gudangcenter->getDestiny())."</small>";
			echo "</td>";
			echo "<td>";
			echo "<small>".htmlspecialchars($gudangcenter->getDocumentLocation())."</small>";
			echo "</td>";
			echo "<td>";
			echo htmlspecialchars($user->getPICName($request->getUserID())->getFullName());
			echo "</td>";
			echo "<td>";
			echo htmlspecialchars(getReadableDate($gudangcenter->getDate()));
			echo "</td>";
			echo "<td>";
			echo htmlspecialchars(getReadableDate($gudangcenter->getExpires()));
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
				echo "Request Balik";
			} else if ($status == 4){
				echo "Tiba di ".$gudangcenter->getOrigin();
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
		$allGudangcenter = $this->params['allgudangcenter'];

		$this->htmlStartPage(getMLText("gudang_center"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");

	$this->contentHeading(getMLText("status_gudang_center"));

	$this->statusGudangCenter();

		$this->contentEnd();
		$this->htmlEndPage();
	} 
	
}
?>