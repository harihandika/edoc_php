<?php
/**
 * Implementation of RequestDocumentSoftCOpy controller
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class which does the busines logic for downloading a document
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Controller_RequestDocumentSoftCopy extends SeedDMS_Controller_Common {

	public function run() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$fulltextservice = $this->params['fulltextservice'];
		$folder = $this->params['folder'];
		// $softcopy = $this->params['softcopy'];


		/* Call preRequestDocumentSoftCopy early, because it might need to modify some
		 * of the parameters.
		 */
		if(false === $this->callHook('preRequestDocumentSoftCopy')) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_preRequestDocumentSoftCopy_failed';
			return false;
		}

		$name = $this->getParam('name');
		$keterangan = $this->getParam('keterangan');
		$keperluan = $this->getParam('keperluan');
		$sequence = $this->getParam('sequence');
		$attributes = $this->getParam('attributes');
		// foreach($attributes as $attrdefid=>$attribute) {
		// 	if($attrdef = $dms->getAttributeDefinition($attrdefid)) {
		// 		if(null === ($ret = $this->callHook('validateAttribute', $attrdef, $attribute))) {
		// 		if($attribute) {
		// 			if(!$attrdef->validate($attribute)) {
		// 				$this->errormsg = getAttributeValidationError($attrdef->getValidationError(), $attrdef->getName(), $attribute);
		// 				return false;
		// 			}
		// 		} elseif($attrdef->getMinValues() > 0) {
		// 			$this->errormsg = array("attr_min_values", array("attrname"=>$attrdef->getName()));
		// 			return false;
		// 		}
		// 		} else {
		// 			if($ret === false)
		// 				return false;
		// 		}
		// 	}
		// }
		$notificationgroups = $this->getParam('notificationgroups');
		$notificationusers = $this->getParam('notificationusers');

		$subSoftCopy = $this->callHook('requestDocumentSoftCopy');
		// if($subSoftCopy === null) {
		// 	$subSoftCopy = $softcopy->requestDocumentSoftCopy($name, $keterangan, $keperluan, $user, $sequence, $attributes);
		// 	if (!is_object($subSoftCopy)) {
		// 		$this->errormsg = "error_occured";
		// 		return false;
		// 	}
		// 	/* Check if additional notification shall be added */
		// 	foreach($notificationusers as $notuser) {
		// 		if($subSoftCopy->getAccessMode($user) >= M_READ)
		// 			$res = $subSoftCopy->addNotify($notuser->getID(), true);
		// 	}
		// 	foreach($notificationgroups as $notgroup) {
		// 		if($subSoftCopy->getGroupAccessMode($notgroup) >= M_READ)
		// 			$res = $subSoftCopy->addNotify($notgroup->getID(), false);
		// 	}
		// } elseif($subSoftCopy === false) {
		// 	if(empty($this->errormsg))
		// 		$this->errormsg = 'hook_addSoftCopy_failed';
		// 	return false;
		// }

		if($fulltextservice && ($index = $fulltextservice->Indexer()) && $subSoftCopy) {
			$idoc = $fulltextservice->IndexedDocument($subSoftCopy);
			if(false !== $this->callHook('preIndexSoftCopy', $subSoftCopy, $idoc)) {
				$index->addDocument($idoc);
				$index->commit();
			}
		}

		if(false === $this->callHook('postRequestDocumentSoftCOpy', $subSoftCopy)) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_postRequestDocumentSoftCopy_failed';
			return false;
		}

		return $subSoftCopy;
	} /* }}} */
}

