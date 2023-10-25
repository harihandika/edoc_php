<?php
/**
 * Implementation of Role manager controller
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
 * Class which does the busines logic for role manager
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Controller_RackLocations extends SeedDMS_Controller_Common {

	public function run() {
	}

	public function addracklocations() {
		$dms = $this->params['dms'];
		$name = $this->params['name'];
		$racklocations = $this->params['racklocations'];

		return($dms->addRackLocations($name, $racklocations));
	}

	public function removeracklocations() {
		$racklocationobj = $this->params['racklocationobj'];
		return $racklocationobj->remove();
	}

	public function editracklocations() {
		$dms = $this->params['dms'];
		$name = $this->params['name'];
		$racklocations = $this->params['racklocations'];
		$racklocationobj = $this->params['racklocationobj'];
		$noaccess = $this->params['noaccess'];

		if ($racklocationobj->getName() != $name)
			$racklocationobj->setName($name);
		if ($racklocationobj->getRackLocations() != $racklocations)
			$racklocationobj->setRackLocations($racklocations);
		$racklocationobj->setNoAccess($noaccess);

		return true;
	}
}
