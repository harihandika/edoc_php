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
class SeedDMS_View_GudangCenter extends SeedDMS_Bootstrap_Style {

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
		$sortusersinlist = $this->params['sortusersinlist'];
		$worklocations = $this->params['allworklocations'];
		$documents = $this->params['documents'];
		$accessop = $this->params['accessobject'];
		$folderid = $folder->getId();

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("folder_title", array("foldername" => htmlspecialchars($folder->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
?>
	<div class="ajax" data-view="ViewFolder" data-action="navigation" data-no-spinner="true" <?php echo ($folder ? "data-query=\"gudangcenterid=".$folder->getID()."\"" : "") ?>></div>
<?php
		$this->contentHeading(getMLText("gudang_center"));
?>

<form class="form-horizontal" action="../op/op.GudangCenter.php" id="form1" name="form1" method="post">
	<?php echo createHiddenFieldWithKey('gudangcenter'); ?>
	<input type="hidden" name="folderid" value="<?php print $folderid;?>">
	<input type="hidden" name="showtree" value="<?php echo showtree();?>">
<?php	

$this->contentContainerStart();

$users = $user->getFullName();
			
$this->formField(
	getMLText("user_request"),
	array(
		'element'=>'text',
		'id'=>'ownerid',
		'name'=>'ownerid',
		'value'=>$users,
		'placeholder'=>'please input requestor',
		'required'=>true,
		'disabled'=>'true'
	)
);


foreach($worklocations as $worklocation) {
	if ($user && $worklocation->isMember($user)){
		$options = htmlspecialchars($worklocation->getName());
	}
}
$this->formField(
	"Lokasi Requestor",	
	array(
		'element'=>'text',
		'name'=>'destiny',
		'placeholder'=>'Input work location',
		'value'=>$options,
		'required'=>true
	)
);

$this->contentContainerEnd();

$options = array();
foreach($documents as $document) {
	$options[] = array(htmlspecialchars($document->getID()), htmlspecialchars($document->getName()) . '   --    ' . htmlspecialchars($document->getOwner()->getFullName()));
}
		$this->formField(
			getMLText("name_document"),
			array(
				'element'=>'select',
				'name'=>'documentid',
				'required'=>true,
				'class'=>'chzn-select',
				'options'=>$options
			)
		);

			$options = array();
			foreach($worklocations as $worklocation) {
					$options[] =  array($worklocation->getName(), htmlspecialchars($worklocation->getName()));
			}
			$this->formField(
				"Lokasi Dokumen",	
				array(
					'element'=>'select',
					'name'=>'origin',
					'class'=>'chzn-select',
					'placeholder'=>'Input work location',
					'options'=>$options,
					'required'=>true
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
				$allUsers = $dms->getAllUsers($sortusersinlist);
				foreach ($allUsers as $userObj) {
						$options[] = array($userObj->getID(), htmlspecialchars($userObj->getLogin() . " - " . $userObj->getFullName()));
				}		
				$this->formField(
					getMLText("pilih_pic"),
					array(
						'element'=>'select',
						'name'=>'notification_users[]',
						'class'=>'chzn-select',
						'attributes'=>array(array('data-placeholder', getMLText('select_pic'))),
						'options'=>$options
					),
					array('field_wrap'=>array('', ($tmp ? '<div class="mandatories"><span>'.getMLText('mandatory_reviewers').':</span> '.implode(', ', $tmp).'</div>' : '')))
				);

				$this->formField(
					getMLText("kode_rak"),
					array(
						'element'=>'input',
						'type'=>'text',
						'id'=>'kode',
						'name'=>'kode',
						'required'=>true
					)
				);

				$options = array();
				$options[] = array('1','rak 1');
				$options[] = array('2','rak 2');
				$options[] = array('3','rak 3');
				$options[] = array('4','rak 4');
				$options[] = array('5','rak 5');
				$options[] = array('6','rak 6');
				$options[] = array('7','rak 7');
				$options[] = array('8','rak 8');
				$options[] = array('9','rak 9');
				$options[] = array('10','rak 10');
				$this->formField(
					getMLText("nomor_rak"),
					array(
						'element'=>'select',
						'name'=>'nomor',
						'options'=>$options
					)
				);
				
				$options = array();
				$options[] = array('1','baris 1');
				$options[] = array('2','baris 2');
				$options[] = array('3','baris 3');
				$options[] = array('4','baris 4');
				$options[] = array('5','baris 5');
				$options[] = array('6','baris 6');
				$options[] = array('7','baris 7');
				$options[] = array('8','baris 8');
				$options[] = array('9','baris 9');
				$options[] = array('10','baris 10');
		
				$this->formField(
					getMLText("baris_rak"),
					array(
						'element'=>'select',
						'name'=>'baris',
						'options'=>$options
					)
				);


		$this->formField(
			getMLText("periode_pinjam"),
			$this->getDateChooser('', "expdate", $this->params['session']->getLanguage())
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

		$attrdefs = $dms->getAllAttributeDefinitions(array(SeedDMS_Core_AttributeDefinition::objtype_folder, SeedDMS_Core_AttributeDefinition::objtype_all));
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
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

		$this->formSubmit("<i class=\"fa fa-save\"></i> ".getMLText('gudang_center'));
?>
</form>
<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
