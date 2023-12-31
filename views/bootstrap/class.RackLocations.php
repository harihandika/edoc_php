<?php
/**
 * Implementation of RoleMgr view
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
 * Class which outputs the html page for RoleMgr view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_RackLocations extends SeedDMS_Bootstrap_Style {

	function js() { /* {{{ */
		$selrole = $this->params['selrole'];


		header('Content-Type: application/javascript');
?>
function checkForm()
{
	msg = new Array();

	if($("#name").val() == "") msg.push("<?php printMLText("js_no_name");?>");
	if (msg != "") {
  	noty({
  		text: msg.join('<br />'),
  		type: 'error',
      dismissQueue: true,
  		layout: 'topRight',
  		theme: 'defaultTheme',
			_timeout: 1500,
  	});
		return false;
	}
	else
		return true;
}

$(document).ready( function() {
	$('body').on('submit', '#form', function(ev){
		if(checkForm()) return;
		event.preventDefault();
	});
	$( "#selector" ).change(function() {
		$('div.ajax').trigger('update', {racklocationid: $(this).val()});
		window.history.pushState({"html":"","pageTitle":""},"", '../out/out.RackLocations.php?racklocationid=' + $(this).val());
	});
});
<?php
	} /* }}} */

	function info() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$selrole = $this->params['selrole'];
		$settings = $this->params['settings'];
		$accessobject = $this->params['accessobject'];

		if($selrole) {
			$this->contentHeading("Rack Info");
			$users = $selrole->getUsers();
			// if($users) {
			// 	echo "<table class=\"table table-condensed\"><thead><tr><th>".getMLText('name')."</th><th></th></tr></thead><tbody>\n";
			// 	foreach($users as $currUser) {
			// 		echo "<tr>";
			// 		echo "<td>";
			// 		echo htmlspecialchars($currUser->getFullName())." (".htmlspecialchars($currUser->getLogin()).")";
			// 		echo "<br /><a href=\"mailto:".htmlspecialchars($currUser->getEmail())."\">".htmlspecialchars($currUser->getEmail())."</a>";
			// 		if($currUser->getComment())
			// 			echo "<br /><small>".htmlspecialchars($currUser->getComment())."</small>";
			// 		echo "</td>";
			// 		echo "<td>";
			// 		if($accessobject->check_view_access(array('UsrMgr', 'RemoveUser'))) {
			// 			echo "<div class=\"list-action\">";
			// 			echo $this->html_link('UsrMgr', array('userid'=>$currUser->getID()), array(), '<i class="fa fa-edit"></i>', false);
			// 			echo $this->html_link('RemoveUser', array('userid'=>$currUser->getID()), array(), '<i class="fa fa-remove"></i>', false);
			// 			echo "</div>";
			// 		}
			// 		echo "</td>";
			// 		echo "</tr>";
			// 	}
			// 	echo "</tbody></table>";
			// }
		}
	} /* }}} */

	function actionmenu() { /* {{{ */

		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$selrole = $this->params['selrole'];
		$accessop = $this->params['accessobject'];

		if($selrole) {
			if(!$selrole->isUsed() && $accessop->check_controller_access('RackLocations', array('action'=>'removeracklocations'))) {
?>
			<form style="display: inline-block;" method="post" action="../op/op.RackLocations.php" >
				<?php echo createHiddenFieldWithKey('removeracklocations'); ?>
				<input type="hidden" name="racklocationid" value="<?php echo $selrole->getID()?>">
				<input type="hidden" name="action" value="removeracklocations">
				<button type="submit" class="btn"><i class="fa fa-remove"></i> <?php echo getMLText("rm_role")?></button>
			</form>
<?php
			}
		}
	} /* }}} */

	function form() { /* {{{ */
		$selrole = $this->params['selrole'];

		$this->showRackLocationForm($selrole);
	} /* }}} */

	function showRackLocationForm($racklocations) { /* {{{ */

		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$accessop = $this->params['accessobject'];

?>

	<form class="form-horizontal" action="../op/op.RackLocations.php" method="post" enctype="multipart/form-data" name="form" id="form">
<?php
		if($racklocations) {
			echo createHiddenFieldWithKey('editracklocations');
?>
	<input type="hidden" name="racklocationid" id="racklocationid" value="<?php print $racklocations->getID();?>">
	<input type="hidden" name="action" value="editracklocations">
<?php
		} else {
			echo createHiddenFieldWithKey('addracklocations');
?>
	<input type="hidden" id="racklocationid" value="0">
	<input type="hidden" name="action" value="addracklocations">
<?php
		}
		$this->formField(
			getMLText("kode_rak"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'kode',
				'name'=>'kode',
				'required'=>true,
				'value'=>($racklocations ? htmlspecialchars($racklocations->getKode()) : '')
			)
		);

		$options = array();
		$options[] = array('1','rak 1', $racklocations && $racklocations->getNomor() == '1');
		$options[] = array('2','rak 2', $racklocations && $racklocations->getNomor() == '2');
		$options[] = array('3','rak 3', $racklocations && $racklocations->getNomor() == '3');
		$options[] = array('4','rak 4', $racklocations && $racklocations->getNomor() == '4');
		$options[] = array('5','rak 5', $racklocations && $racklocations->getNomor() == '5');
		$options[] = array('6','rak 6', $racklocations && $racklocations->getNomor() == '6');
		$options[] = array('7','rak 7', $racklocations && $racklocations->getNomor() == '7');
		$options[] = array('8','rak 8', $racklocations && $racklocations->getNomor() == '8');
		$options[] = array('9','rak 9', $racklocations && $racklocations->getNomor() == '9');
		$options[] = array('10','rak 10', $racklocations && $racklocations->getNomor() == '10');
		$this->formField(
			getMLText("nomor_rak"),
			array(
				'element'=>'select',
				'name'=>'nomor',
				'options'=>$options
			)
		);
		
		$options = array();
		$options[] = array('1','baris 1', $racklocations && $racklocations->getBaris() == '1');
		$options[] = array('2','baris 2', $racklocations && $racklocations->getBaris() == '2');
		$options[] = array('3','baris 3', $racklocations && $racklocations->getBaris() == '3');
		$options[] = array('4','baris 4', $racklocations && $racklocations->getBaris() == '4');
		$options[] = array('5','baris 5', $racklocations && $racklocations->getBaris() == '5');
		$options[] = array('6','baris 6', $racklocations && $racklocations->getBaris() == '6');
		$options[] = array('7','baris 7', $racklocations && $racklocations->getBaris() == '7');
		$options[] = array('8','baris 8', $racklocations && $racklocations->getBaris() == '8');
		$options[] = array('9','baris 9', $racklocations && $racklocations->getBaris() == '9');
		$options[] = array('10','baris 10', $racklocations && $racklocations->getBaris() == '10');

		$this->formField(
			getMLText("baris_rak"),
			array(
				'element'=>'select',
				'name'=>'baris',
				'options'=>$options
			)
		);

		$this->formField(
			getMLText("fisik_location"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'fisik',
				'name'=>'fisik',
				'value'=>($racklocations ? htmlspecialchars($racklocations->getFisik()) : '')
			)
		);

		$this->formField(
			getMLText("keterangan"),
			array(
				'element'=>'textarea',
				'type'=>'text',
				'id'=>'keterangan',
				'name'=>'keterangan',
				'value'=>($racklocations ? htmlspecialchars($racklocations->getKeterangan()) : '')
			)
		);

		if($racklocations && $accessop->check_controller_access('RackLocations', array('action'=>'editracklocations')) || !$racklocations && $accessop->check_controller_access('RackLocations', array('action'=>'addracklocations'))) {
			$this->formSubmit("<i class=\"fa fa-save\"></i> ".getMLText($racklocations ? "save" : "add_racklocation"));
		}
?>
	</form>
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$accessop = $this->params['accessobject'];
		$selrole = $this->params['selrole'];
		$allracklocations = $this->params['allracklocations'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");

		$this->contentHeading(getMLText("rack_location"));
?>
<div class="row-fluid">
<div class="span4">
<form class="form-horizontal">
<?php
		$options = array();
		$options[] = array("-1", getMLText("choose_racklocation"));
		if($accessop->check_controller_access('RackLocations', array('action'=>'addracklocations'))) {
			$options[] = array("0", getMLText("add_racklocation"));
		}
		foreach ($allracklocations as $currRole) {
			$options[] = array($currRole->getID(), htmlspecialchars($currRole->getKode()), $selrole && $currRole->getID()==$selrole->getID());
		}
		$this->formField(
			null, //getMLText("selection"),
			array(
				'element'=>'select',
				'id'=>'selector',
				'class'=>'chzn-select',
				'options'=>$options
			)
		);
?>
</form>
	<div class="ajax" style="margin-bottom: 15px;" data-view="RackLocations" data-action="actionmenu" <?php echo ($selrole ? "data-query=\"racklocationid=".$selrole->getID()."\"" : "") ?>></div>
<?php if($accessop->check_view_access($this, array('action'=>'info'))) { ?>
	<div class="ajax" data-view="RackLocations" data-action="info" <?php echo ($selrole ? "data-query=\"racklocationid=".$selrole->getID()."\"" : "") ?>></div>
<?php } ?>
</div>

<div class="span8">
<?php if($accessop->check_view_access($this, array('action'=>'form'))) { ?>
	<div class="well">
		<div class="ajax" data-view="RackLocations" data-action="form" <?php echo ($selrole ? "data-query=\"racklocationid=".$selrole->getID()."\"" : "") ?>></div>
	</div>
<?php } else {
	$this->errorMsg(getMLText('access_denied'));
} ?>
</div>
</div>

<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
