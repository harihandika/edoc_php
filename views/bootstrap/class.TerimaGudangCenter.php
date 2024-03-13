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
class SeedDMS_View_TerimaGudangCenter extends SeedDMS_Bootstrap_Style {

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

function terimaGudangCenter() { /* {{{ */

	$dms = $this->params['dms'];
	$user = $this->params['user'];
	$allGudangcenter = $this->params['allgudangcenter'];
	$showtree = $this->params['showtree'];
	$previewwidth = $this->params['previewWidthList'];
	?>
	<table id="myTable" class="table">
		<thead>
		<tr><th><?php printMLText('name'); ?></th><th><?php printMLText('origin');?></th><th><?php printMLText('destiny');?></th><th><?php printMLText('location');?></th><th><?php printMLText('owner'); ?></th><th><?php printMLText('from_date'); ?></th><th><?php printMLText('to_date'); ?></th><th>
			<?php printMLText('status'); ?></th>
		<th></th></tr>
		</thead>
		<tbody>
<?php

$res=$user->getMandatoryApproverUser();
$userRequesters = array();
if($res) {
	foreach ($res as $r) {
			$userRequesters[] = $dms->getUser($r['userID']);
	}
};

$documents = $dms->getAllDocuments();

if($res){
	foreach ($allGudangcenter as $gudangcenter) {
			foreach ( $userRequesters as $s){
		foreach ($s->getNotifications(T_GUDANGCENTER) as $request){
			foreach ($documents as $document) {
				if($document->getID() == $gudangcenter->getDocumentID()){
					if ($request->getTarget() == $gudangcenter->getID()){
						$status = $gudangcenter->getStatus();
						if($status != 0 ||  $status != -1 ){
							echo "<td>";
			
							$docID = $document->getID();
							$latestContent = $document->getLatestContent();
			
							echo "<img draggable=\"false\" class=\"mimeicon\" width=\"".$previewwidth."\" src=\"".$this->getMimeIcon($latestContent->getFileType())."\" ".($previewwidth ? "width=\"".$previewwidth."\"" : "")."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">". "      ".
							"<a draggable=\"false\" href=\"../out/out.ViewDocumentReq.php?documentid=".$docID."&showtree=".$showtree."\">" . htmlspecialchars($document->getName()) . "</a>"."<br />";
				
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
							echo htmlspecialchars($gudangcenter->getOwner()->getFullName());
							echo "</td>";
							echo "<td>";
							echo htmlspecialchars(getReadableDate($gudangcenter->getDate()));
							echo "</td>";
							echo "<td>";
							echo htmlspecialchars(getReadableDate($gudangcenter->getExpires()));
							echo "</td>";
							echo "<td>";
							if ($status == 1){
								print "<a href='../op/op.TerimaGudangCenter.php?id=".$gudangcenter->getID()."&type=gudangcenter&action=app' class=\"btn btn-mini\"><i class=\"fa fa-remove\"></i> "."Approve"."</a>";
								print "<a href='../op/op.TerimaGudangCenter.php?id=".$gudangcenter->getID()."&type=gudangcenter&action=rej' class=\"btn btn-mini\"><i class=\"fa fa-remove\"></i> "."Reject"."</a>";
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
	}
	}
}else{

		foreach ($allGudangcenter as $gudangcenter) {
			foreach ($user->getNotifications(T_GUDANGCENTER) as $request){
				foreach ($documents as $document) {
					if($document->getID() == $gudangcenter->getDocumentID()){
						if ($request->getTarget() == $gudangcenter->getID()){
											// echo "<tr".($currUser->isDisabled() ? " class=\"error\"" : "").">";
											echo "<td>";
				
											$docID = $document->getID();
											$latestContent = $document->getLatestContent();
											$res = $user->getMandatoryApprovers();
			foreach ($res as $r) {
				if($r['approverUserID'] > 0) {
					$u = $dms->getUser($r['approverUserID']);
					$tmp[] =  htmlspecialchars($u->getFullName().' ('.$u->getLogin().')');
				}
			};
			foreach($tmp as $t){
			}
								
											echo "<img draggable=\"false\" class=\"mimeicon\" width=\"".$previewwidth."\" src=\"".$this->getMimeIcon($latestContent->getFileType())."\" ".($previewwidth ? "width=\"".$previewwidth."\"" : "")."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">". "      ".
											"<a draggable=\"false\" href=\"../out/out.ViewDocumentReq.php?documentid=".$docID."&showtree=".$showtree."\">" . htmlspecialchars($document->getName()) . "</a>"."<br />".
											"<b>"."<span style=\"font-size: 85%; font-style: italic; color: #666; \">".getMLText('owner').": <b>".htmlspecialchars($gudangcenter->getOwner()->getFullName())."</b>, ".getMLText('creation_date').": <b>".getReadableDate($document->getDate())."</b>"."</span>"."<br />";
								
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
											echo htmlspecialchars($gudangcenter->getOwner()->getFullName());
											echo "</td>";
											echo "<td>";
											echo htmlspecialchars(getReadableDate($gudangcenter->getDate()));
											echo "</td>";
											echo "<td>";
											echo htmlspecialchars(getReadableDate($gudangcenter->getExpires()));
											echo "</td>";
											echo "<td>";
											$status = $gudangcenter->getStatus();
											if ($status == 1){
												echo "Waiting Approval By $t";
											} else if ($status == -1){
												echo "Decline by user";
											} else if ($status == 2){
												echo "Approve"
?>
<!-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumen Laporan Customer</title>
</head>
<body>
    <form action="../pdf.php" method="POS T">
        <input type="submit" name="pdf" value="Download PDF"/>
    </form>

</body>
</html> -->
<?php
											} else if ($status == -2){
												echo "Reject";
											} else if ($status == 3){
												echo "Request Balik";
											} else if ($status == 4){
												echo "Tiba di ".$gudangcenter->getOrigin();
											} else {
											print "<a href='../op/op.TerimaGudangCenter.php?id=".$gudangcenter->getID()."&type=gudangcenter&action=rec' class=\"btn btn-mini\"><i class=\"fa fa-remove\"></i> "."Receive"."</a>";
											
											print "<a href='../op/op.TerimaGudangCenter.php?id=".$gudangcenter->getID()."&type=gudangcenter&action=dec' class=\"btn btn-mini\"><i class=\"fa fa-remove\"></i> "."Reject by User"."</a>";
										}
								
											echo "</td>";
											echo "<td>";
											if($status == 3 ){
											print "<a href='../op/op.TerimaGudangCenter.php?id=".$gudangcenter->getID()."&type=gudangcenter&action=done' class=\"btn btn-mini\"><i class=\"fa fa-remove\"></i> "."Finish"."</a>";
											}else if($status == 4){
												echo "Tiba di ".$gudangcenter->getOrigin();
												
											}else {
												print"<a href='../op/op.TerimaGudangCenter.php?id=".$gudangcenter->getID()."&type=gudangcenter&action=return' class=\"btn btn-mini\"><i class=\"fa fa-remove\"></i> "."RequestBalik"."</a>";
											}
											echo "</td>";
											echo "</tr>";
						}  
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

		$this->htmlStartPage(getMLText("terima_gudang_center"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");

		$this->contentHeading(getMLText("terima_gudang_center"));

	$this->terimaGudangCenter();

		$this->contentEnd();
		$this->htmlEndPage();
	} 
	
}
?>