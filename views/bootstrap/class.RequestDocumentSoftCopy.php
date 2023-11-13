<?php
/**
 * Implementation of AddSubFolder view
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
 * Class which outputs the html page for AddSubFolder view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_RequestDocumentSoftCopy extends SeedDMS_Bootstrap_Style {

	function js() { /* {{{ */
		$strictformcheck = $this->params['strictformcheck'];
		header('Content-Type: application/javascript; charset=UTF-8');
?>
$(document).ready( function() {
	$("#form1").validate({
		invalidHandler: function(e, validator) {
			noty({
				text:  (validator.numberOfInvalids() == 1) ? "<?php printMLText("js_form_error");?>".replace('#', validator.numberOfInvalids()) : "<?php printMLText("js_form_errors");?>".replace('#', validator.numberOfInvalids()),
				type: 'error',
				dismissQueue: true,
				layout: 'topRight',
				theme: 'defaultTheme',
				timeout: 1500,
			});
		},
		highlight: function(e, errorClass, validClass) {
			$(e).parent().parent().removeClass(validClass).addClass(errorClass);
		},
		unhighlight: function(e, errorClass, validClass) {
			$(e).parent().parent().removeClass(errorClass).addClass(validClass);
		},
		messages: {
			name: "<?php printMLText("js_no_name");?>",
			comment: "<?php printMLText("js_no_comment");?>"
		},
	});
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$strictformcheck = $this->params['strictformcheck'];
		$orderby = $this->params['orderby'];
		$enableadminrevapp = $this->params['enableadminrevapp'];
		$enableownerrevapp = $this->params['enableownerrevapp'];
		$enableselfrevapp = $this->params['enableselfrevapp'];
		$worklocations = $this->params['allworklocations'];

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("folder_title", array("foldername" => htmlspecialchars($folder->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
//		$this->pageNavigation($this->getFolderPathHTML($folder, true), "view_folder", $folder);

/******************************************** *************/
$docAccess = $folder->getReadAccessList($enableadminrevapp, $enableownerrevapp);
?>
	<div class="ajax" data-view="ViewFolder" data-action="navigation" data-no-spinner="true" <?php echo ($folder ? "data-query=\"folderid=".$folder->getID()."\"" : "") ?>></div>
<?php
		$this->contentHeading(getMLText("request_document_soft_copy"));
		$this->contentContainerStart();
?>

<form class="form-horizontal" action="../op/op.RequestDocumentSoftCopy.php" id="form1" name="form1" method="post">
	<?php echo createHiddenFieldWithKey('requestdocumentsoftcopy'); ?>
	<input type="hidden" name="folderid" value="<?php print $folder->getId();?>">
	<input type="hidden" name="showtree" value="<?php echo showtree();?>">
<?php	
		$this->formField(
			getMLText("name_document"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'name',
				'name'=>'name',
				'required'=>true
			)
		);
		$this->formField(
			getMLText("keterangan"),
			array(
				'element'=>'textarea',
				'name'=>'keterangan',
				'rows'=>4,
				'cols'=>80,
				'required'=>$strictformcheck
			)
		);
		$res=$user->getMandatoryReviewers();
				$tmp = array();
				if($res) {
					foreach ($res as $r) {
						if($r['reviewerUserID'] > 0) {
							$u = $dms->getUser($r['reviewerUserID']);
							$tmp[] =  htmlspecialchars($u->getFullName().' ('.$u->getLogin().')');
						}
					}
				}
		$options = array();
				foreach ($docAccess["users"] as $usr) {
					if (!$enableselfrevapp && $usr->getID()==$user->getID()) continue; 
					$mandatory=false;
					foreach ($res as $r) if ($r['reviewerUserID']==$usr->getID()) $mandatory=true;

					$option = array($usr->getID(), htmlspecialchars($usr->getLogin()." - ".$usr->getFullName()), null);
					if ($mandatory) $option[] = array(array('disabled', 'disabled'));
					$options[] = $option;
				}

				$this->formField(
					getMLText("pilih_pic"),
					array(
						'element'=>'select',
						'name'=>'indReviewers[]',
						'class'=>'chzn-select',
						'attributes'=>array(array('data-placeholder', getMLText('select_pic'))),
						'multiple'=>true,
						'options'=>$options
					),
					array('field_wrap'=>array('', ($tmp ? '<div class="mandatories"><span>'.getMLText('mandatory_reviewers').':</span> '.implode(', ', $tmp).'</div>' : '')))
				);

				$options = array();
				foreach($worklocations as $worklocation) {
					$options[] = array($worklocation->getID(), htmlspecialchars($worklocation->getName()), ($user && $worklocation->isMember($user)));
				}

				$this->formField(
					"Location",	
					array(
						'element'=>'select',
						'name'=>'worklocations[]',
						'class'=>'chzn-select',
						'multiple'=>true,
						'placeholder'=>'Click to select work location',
						'options'=>$options
					)
				);

		$options = array();
		$options[] = array('Keperluan Audit','Keperluan Audit');
		$options[] = array('Keperluan Tender','Keperluan Tender');
		$options[] = array('Keperluan Review','Keperluan Review');
		$options[] = array('Lain-lain','Lain-lain');
		$this->formField(
			getMLText("keperluan"),
			array(
				'element'=>'select',
				'name'=>'nomor',
				'options'=>$options
			)
		);

		$attrdefs = $dms->getAllAttributeDefinitions(array(SeedDMS_Core_AttributeDefinition::objtype_folder, SeedDMS_Core_AttributeDefinition::objtype_all));
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				/* The second parameter is null, to make this function call equal
				 * to 'editFolderAttribute', which expects the folder as the second
				 * parameter.
				 */
				$arr = $this->callHook('addFolderAttribute', null, $attrdef);
				if(is_array($arr)) {
					if($arr) {
						$this->formField($arr[0], $arr[1], isset($arr[2]) ? $arr[2] : null);
					}
				} elseif(is_string($arr)) {
					echo $arr;
				} else {
					$this->formField(htmlspecialchars($attrdef->getName()), $this->getAttributeEditField($attrdef, ''));
				}
			}
		}
		/* The second parameter is null, to make this function call equal
		 * to 'editFolderAttributes', which expects the folder as the second
		 * parameter.
		 */
		$arrs = $this->callHook('addFolderAttributes', null);
		if(is_array($arrs)) {
			foreach($arrs as $arr) {
				$this->formField($arr[0], $arr[1], isset($arr[2]) ? $arr[2] : null);
			}
		} elseif(is_string($arrs)) {
			echo $arrs;
		}

		$this->formSubmit("<i class=\"fa fa-save\"></i> ".getMLText('request_document_soft_copy'));
?>
</form>
<?php
		$this->contentContainerEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
