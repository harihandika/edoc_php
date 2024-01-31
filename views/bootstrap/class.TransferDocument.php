<?php
/**
 * Implementation of TransferDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2017 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for TransferDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2017 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_TransferDocument extends SeedDMS_Bootstrap_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$allusers = $this->params['allusers'];
		$document = $this->params['document'];
		$folder = $this->params['folder'];
		$accessobject = $this->params['accessobject'];
		$enableadminrevapp = $this->params['enableadminrevapp'];
		$enableownerrevapp = $this->params['enableownerrevapp'];
		$enableselfrevapp = $this->params['enableselfrevapp'];

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->contentHeading(getMLText("transfer_document"));

		$docAccess = $folder->getReadAccessList($enableadminrevapp, $enableownerrevapp);
?>
<form class="form-horizontal" action="../op/op.TransferDocument.php" name="form1" method="post">
<input type="hidden" name="documentid" value="<?php print $document->getID();?>">
<?php echo createHiddenFieldWithKey('transferdocument'); ?>
<?php
		$html = '<select name="userid" class="chzn-select">';
		$owner = $document->getOwner();
		$hasusers = false; // set to true if at least one user is found
		foreach ($allusers as $currUser) {
			if ($currUser->isGuest() || ($currUser->getID() == $owner->getID()))
				continue;

			$hasusers = true;
			$html .= "<option value=\"".$currUser->getID()."\"";
			if($folder->getAccessMode($currUser) < M_READ)
				$html .= " disabled data-warning=\"".getMLText('transfer_no_read_access')."\"";
			elseif($folder->getAccessMode($currUser) < M_READWRITE)
				$html .= " data-warning=\"".getMLText('transfer_no_write_access')."\"";
			$html .= ">" . htmlspecialchars($currUser->getLogin()." - ".$currUser->getFullName());
		}
		$html .= '</select>';
		if($hasusers) {
			$this->contentContainerStart();
			$this->formField(
				getMLText("transfer_to_user"),
				$html
			);


			$res=$user->getMandatoryApprovers();
			$tmp = array();
			if($res) {
				foreach ($res as $r) {
					if($r['approverUserID'] > 0) {
						$u = $dms->getUser($r['approverUserID']);
						$tmp[] =  htmlspecialchars($u->getFullName().' ('.$u->getLogin().')');
					}
				}
			}
		
		$options = array();
			foreach ($docAccess["users"] as $usr) {
				if (!$enableselfrevapp && $usr->getID()==$user->getID()) continue; 

				$mandatory=false;
				foreach ($res as $r) if ($r['approverUserID']==$usr->getID()) $mandatory=true;
				
				$option = array($usr->getID(), htmlspecialchars($usr->getLogin()." - ".$usr->getFullName()), null);
				if ($mandatory) $option[] = array(array('disabled', 'disabled'));
				$options[] = $option;
			}
	
			$this->formField(
				getMLText("individuals"),
				array(
					'element'=>'select',
					'name'=>'indApprovers[]',
					'class'=>'chzn-select',
					'attributes'=>array(array('data-placeholder', getMLText('select_ind_approvers'))),
					'multiple'=>true,
					'options'=>$options
				),
				array('field_wrap'=>array('', ($tmp ? '<div class="mandatories"><span>'.getMLText('mandatory_approvers').':</span> '.implode(', ', $tmp).'</div>' : '')))
			);
			

			if($accessobject->check_controller_access('TransferDocument', array('action'=>'run'))) {
				$this->formSubmit("<i class=\"fa fa-exchange\"></i> ".getMLText('transfer_document'));
			}
			$this->contentContainerEnd();
		} else {
			$this->warningMsg('transfer_no_users');
		}

?>
</form>
<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
