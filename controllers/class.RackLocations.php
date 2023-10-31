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
		$kode = $this->params['kode'];
		$nomor = $this->params['nomor'];
		$baris = $this->params['baris'];
		$fisik = $this->params['fisik'];
		$keterangan = $this->params['keterangan'];

		return($dms->addRackLocations($kode, $nomor, $baris, $fisik, $keterangan));
	}

	public function removeracklocations() {
		$racklocationobj = $this->params['racklocationobj'];
		return $racklocationobj->remove();
	}

	public function editracklocations() {
		$dms = $this->params['dms'];
		$kode = $this->params['kode'];
		$nomor = $this->params['nomor'];
		$baris = $this->params['baris'];
		$fisik = $this->params['fisik'];
		$keterangan = $this->params['keterangan'];
		$racklocationobj = $this->params['racklocationobj'];
		$noaccess = $this->params['noaccess'];


		if ($racklocationobj->getKode() != $kode)
			$racklocationobj->setKode($kode);
		if ($racklocationobj->getNomor() != $nomor)
			$racklocationobj->setNomor($nomor);
		if ($racklocationobj->getBaris() != $baris)
			$racklocationobj->setBaris($baris);
		if ($racklocationobj->getFisik() != $fisik)
			$racklocationobj->setFisik($fisik);
		if ($racklocationobj->getKeterangan() != $keterangan)
			$racklocationobj->setKeterangan($keterangan);
		$racklocationobj->setNoAccess($noaccess);

		return true;
	}
}
