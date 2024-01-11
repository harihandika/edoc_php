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

		function requestSoftCopy($notifications,$deleteaction=true) { /* {{{ */

		if (count($notifications)==0) {
			printMLText("empty_notify_list");
		}
		else {
			$previewer = new SeedDMS_Preview_Previewer($this->cachedir, $this->previewwidth, $this->timeout, $this->xsendfile);
			$previewer->setConverters($this->previewconverters);

			print "<table class=\"table table-condensed\">";
			print "<thead>\n<tr>\n";
			print "<th></th>\n";
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("status")."</th>\n";
			print "<th>".getMLText("action")."</th>\n";
			print "<th></th>\n";
			print "</tr></thead>\n<tbody>\n";
			foreach ($notifications as $notification) {
				$doc = $this->dms->getDocument($notification->getTarget());

				if (is_object($doc)) {
					$doc->verifyLastestContentExpriry();
					echo $this->documentListRowStart($doc);
					$txt = $this->callHook('documentListItem', $doc, $previewer, true, 'managenotify');
					if(is_string($txt))
						echo $txt;
					else {
						echo $this->documentListRow($doc, $previewer, true);
					}
					print "<td>";
					if ($deleteaction) print "<a href='../op/op.ManageNotify.php?id=".$doc->getID()."&type=document&action=del' class=\"btn btn-mini\"><i class=\"fa fa-remove\"></i> ".getMLText("delete")."</a>";
					else print "<a href='../out/out.DocumentNotify.php?documentid=".$doc->getID()."' class=\"btn btn-mini\">".getMLText("edit")."</a>";
					print "</td>\n";
					echo $this->documentListRowEnd($doc);

				}
			}
			print "</tbody></table>";
		}
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
	?>
	<table id="myTable" class="table table-condensed">
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
			print "<a href='../op/op.Tasks.php?id=".$requestsoftcopy->getID()."&type=requestsoftcopy&action=del' class=\"btn btn-mini\"><i class=\"fa fa-remove\"></i> "."Approve"."</a>";
			print "<a href='../op/op.Tasks.php?id=".$requestsoftcopy->getID()."&type=requestsoftcopy&action=del' class=\"btn btn-mini\"><i class=\"fa fa-remove\"></i> "."Reject"."</a>";
			print "<a href='../op/op.Tasks.php?id=".$requestsoftcopy->getID()."&type=requestsoftcopy&action=del' class=\"btn btn-mini\"><i class=\"fa fa-remove\"></i> "."Delete"."</a>";
			echo "</td>";
			echo "</tr>";
		}
	}  
	}
	$this->columnEnd();
	
	echo "</tbody>";
	echo "</table>";

		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>