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
class SeedDMS_View_Tasks extends SeedDMS_Bootstrap_Style {

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

function requestSoftCopy() { /* {{{ */

	$dms = $this->params['dms'];
	$user = $this->params['user'];
	$allRequestsoftcopy = $this->params['allrequestsoftcopy'];
	?>
	<table id="myTable" class="table">
		<thead>
		<tr><th><?php printMLText('name'); ?></th><th><?php printMLText('keperluan');?></th><th><?php printMLText('owner'); ?></th><th><?php printMLText('action'); ?></th><th></th></tr>
		</thead>
		<tbody>
<?php
		foreach ($allRequestsoftcopy as $requestsoftcopy) {
			// if ($user->getID() == $requestsoftcopy->getOwner()->getID()){
			foreach ($user->getNotifications(T_REQUESTSOFTCOPY) as $request){
if ($request->getTarget() == $requestsoftcopy->getID()){
			// echo "<tr".($currUser->isDisabled() ? " class=\"error\"" : "").">";
			echo "<td>";
			echo htmlspecialchars($requestsoftcopy->getName())."<br />";
			echo "<small>".htmlspecialchars($requestsoftcopy->getKeterangan())."</small>";
			echo "</td>";
			echo "<td>";
			echo "<small>".htmlspecialchars($requestsoftcopy->getKeperluan())."</small>";
			echo "</td>";
			echo "<td>";
			echo htmlspecialchars($requestsoftcopy->getOwner()->getFullName());
			echo "</td>";
			echo "<td>";
			$status = $requestsoftcopy->getStatus();
			if ($status == 1){
			echo "approve";
			} else if ($status == -1){
				echo "decline";
			} else {
			print "<a href='../op/op.Tasks.php?id=".$requestsoftcopy->getID()."&type=requestsoftcopy&action=app' class=\"btn btn-mini\"><i class=\"fa fa-remove\"></i> "."Approve"."</a>";
			print "<a href='../op/op.Tasks.php?id=".$requestsoftcopy->getID()."&type=requestsoftcopy&action=rej' class=\"btn btn-mini\"><i class=\"fa fa-remove\"></i> "."Reject"."</a>";
		}
			echo "<td>";
			print "<a href='../op/op.Tasks.php?id=".$requestsoftcopy->getID()."&type=requestsoftcopy&action=del' class=\"btn btn-mini\"><i class=\"fa fa-remove\"></i> "."Delete"."</a>";
			echo "</td>";

			echo "</td>";
			echo "</tr>";
}  
		}
	}
	?></table><?php
} /* }}} */
function statusRequestSoftCopy() { /* {{{ */

	$dms = $this->params['dms'];
	$user = $this->params['user'];
	$allRequestsoftcopy = $this->params['allrequestsoftcopy'];
	?>
	<table id="myTable" class="table">
		<thead>
		<tr><th><?php printMLText('name'); ?></th><th><?php printMLText('keperluan');?></th><th><?php print('PIC'); ?></th><th><?php printMLText('status'); ?></th><th></th></tr>
		</thead>
		<tbody>
<?php
		foreach ($allRequestsoftcopy as $requestsoftcopy) {
			foreach ($requestsoftcopy->getOwner()->getPICNotifications(T_REQUESTSOFTCOPY) as $request){
				if ($request->getTarget() == $requestsoftcopy->getID() && $user->getID() == $requestsoftcopy->getOwner()->getID()){
			
			echo "<td>";
			echo htmlspecialchars($requestsoftcopy->getName())."<br />";
			echo "<small>".htmlspecialchars($requestsoftcopy->getKeterangan())."</small>";
			echo "</td>";
			echo "<td>";
			echo "<small>".htmlspecialchars($requestsoftcopy->getKeperluan())."</small>";
			echo "</td>";
			echo "<td>";
			echo htmlspecialchars($user->getPICName($request->getUserID())->getFullName());
			echo "</td>";
			echo "<td>";
			$status = $requestsoftcopy->getStatus();
			if($status == 0 ){
				echo "pending";
			} else if ($status == 1){
				echo "approve";
			} else if ($status == -1){
				echo "decline";
			}
			echo "</td>";
			echo "</tr>";
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

		$this->rowStart();
		$this->columnStart(6);
		$this->contentHeading(getMLText("request_soft_copy"));
	$this->columnEnd();
	$this->columnStart(6);
	$this->contentHeading(getMLText("status"));
	$this->columnEnd();
	$this->rowEnd();

	$this->rowStart();
	$this->columnStart(6);
	$this->requestSoftCopy();
	$this->columnEnd();
	$this->columnStart(6);
	$this->statusRequestSoftCopy();
	$this->columnEnd();
	$this->rowEnd();
	
	// echo "</tbody>";
	// echo "</table>";

		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>