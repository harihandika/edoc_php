<?php
/**
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
class SeedDMS_Controller_RequestHardCopy extends SeedDMS_Controller_Common {

	public function run() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$fulltextservice = $this->params['fulltextservice'];
		$folder = $this->params['folder'];

		if(false === $this->callHook('preRequestHardCopy')) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_preRequestHardCopy_failed';
			return false;
		}
		
		$documentid = $this->getParam('documentid');
		$keterangan = $this->getParam('keterangan');
		$keperluan = $this->getParam('keperluan');
		$origin = $this->getParam('origin');
		$destiny = $this->getParam('destiny');
		$expires = $this->getParam('expires');
		$owner = $this->getParam('owner');
		$attributes = $this->getParam('attributes');
		$version_comment = $this->getParam('versioncomment');
		$filetype = $this->getParam('filetype');
		$reviewers = $this->getParam('reviewers');
		$approvers = $this->getParam('approvers');
		$reqversion = $this->getParam('reqversion');
		$workflow = $this->getParam('workflow');
		$status = $this->getParam('status');

		foreach($attributes as $attrdefid=>$attribute) {
			if($attrdef = $dms->getAttributeDefinition($attrdefid)) {
				if(null === ($ret = $this->callHook('validateAttribute', $attrdef, $attribute))) {
				if($attribute) {
					switch($attrdef->getType()) {
					case SeedDMS_Core_AttributeDefinition::type_date:
						$attribute = date('Y-m-d', makeTsFromDate($attribute));
						break;
					}
					if(!$attrdef->validate($attribute)) {
						$this->errormsg = getAttributeValidationError($attrdef->getValidationError(), $attrdef->getName(), $attribute);
						return false;
					}
				} elseif($attrdef->getMinValues() > 0) {
					$this->errormsg = array("attr_min_values", array("attrname"=>$attrdef->getName()));
					return false;
				}
				} else {
					if($ret === false)
						return false;
				}
			}
		}
		$notificationgroups = $this->getParam('notificationgroups');
		$notificationusers = $this->getParam('notificationusers');

		$requestHardCopy = $this->callHook('requestHardCopy');
		if($requestHardCopy === null) {
			$requestHardCopy = $folder->requestHardCopy($documentid, $keterangan, $keperluan, $owner,  $attributes, $reviewers, $approvers, $status, $expires, $origin, $destiny);
			if (!is_object($requestHardCopy)) {
				$this->errormsg = "error_occured";
				return false;
			}
			/* Check if additional notification shall be added */
			foreach($notificationusers as $notuser) {
				if($requestHardCopy->getAccessMode($user) >= M_READ)
					$res = $requestHardCopy->addNotify($notuser->getID(), true);
			}

			foreach($notificationgroups as $notgroup) {
				if($requestHardCopy->getGroupAccessMode($notgroup) >= M_READ)
					$res = $requestHardCopy->addNotify($notgroup->getID(), false);
			}
		} elseif($requestHardCopy === false) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_addHardCopy_failed';
			return false;
		}

		if($fulltextservice && ($index = $fulltextservice->Indexer()) && $requestHardCopy) {
			$idoc = $fulltextservice->IndexedDocument($requestHardCopy);
			if(false !== $this->callHook('preIndexHardCopy', $requestHardCopy, $idoc)) {
				$index->addDocument($idoc);
				$index->commit();
			}
		}

		if(false === $this->callHook('postRequestHardCopy', $requestHardCopy)) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_postRequestHardCopy_failed';
			return false;
		}

		return $requestHardCopy;
	} /* }}} */
}

