<?php
/**
 * Implementation of the user object in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Uwe Steinmann
 * @version    Release: 6.0.15
 */

/**
 * Class to represent a role in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2016 Uwe Steinmann
 * @version    Release: 6.0.15
 */
class SeedDMS_Core_Role { /* {{{ */
	/**
	 * @var integer id of role
	 *
	 * @access protected
	 */
	var $_id;

	/**
	 * @var string name of role
	 *
	 * @access protected
	 */
	var $_name;

	/**
	 * @var string role of user. Can be one of SeedDMS_Core_User::role_user,
	 *      SeedDMS_Core_User::role_admin, SeedDMS_Core_User::role_guest
	 *
	 * @access protected
	 */
	var $_role;

	/**
	 * @var array list of status without access
	 *
	 * @access protected
	 */
	var $_noaccess;

	/**
	 * @var object reference to the dms instance this user belongs to
	 *
	 * @access protected
	 */
	var $_dms;

	const role_user = '0';
	const role_admin = '1';
	const role_guest = '2';

	function __construct($id, $name, $role, $noaccess=array()) { /* {{{ */
		$this->_id = $id;
		$this->_name = $name;
		$this->_role = $role;
		$this->_noaccess = $noaccess;
		$this->_dms = null;
	} /* }}} */

	/**
	 * Create an instance of a role object
	 *
	 * @param string|integer $id Id, login name, or email of user, depending
	 * on the 3rd parameter.
	 * @param object $dms instance of dms
	 * @param string $by search by [name|email]. If 'name' is passed, the method
	 * will check for the 4th paramater and also filter by email. If this
	 * parameter is left empty, the user will be search by its Id.
	 * @param string $email optional email address if searching for name
	 * @return object instance of class SeedDMS_Core_User
	 */
	public static function getInstance($id, $dms, $by='') { /* {{{ */
		$db = $dms->getDB();

		switch($by) {
		case 'name':
			$queryStr = "SELECT * FROM `tblRoles` WHERE `name` = ".$db->qstr($id);
			break;
		default:
			$queryStr = "SELECT * FROM `tblRoles` WHERE id = " . (int) $id;
		}

		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		$resArr = $resArr[0];

		$role = new self($resArr["id"], $resArr["name"], $resArr["role"], $resArr['noaccess'] ? explode(',', $resArr['noaccess']) : array());
		$role->setDMS($dms);
		return $role;
	} /* }}} */

	public static function getAllInstances($orderby, $dms) { /* {{{ */
		$db = $dms->getDB();

		if($orderby == 'name')
			$queryStr = "SELECT * FROM `tblRoles` ORDER BY `name`";
		else
			$queryStr = "SELECT * FROM `tblRoles` ORDER BY `id`";
		$resArr = $db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		$roles = array();

		for ($i = 0; $i < count($resArr); $i++) {
			$role = new self($resArr[$i]["id"], $resArr[$i]["name"], $resArr[$i]["role"], explode(',', $resArr[$i]['noaccess']));
			$role->setDMS($dms);
			$roles[$i] = $role;
		}

		return $roles;
} /* }}} */

	function setDMS($dms) {
		$this->_dms = $dms;
	}

	function getID() { return $this->_id; }

	function getName() { return $this->_name; }

	function setName($newName) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblRoles` SET `name` =".$db->qstr($newName)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_name = $newName;
		return true;
	} /* }}} */

	function isAdmin() { return ($this->_role == SeedDMS_Core_Role::role_admin); }

	function isGuest() { return ($this->_role == SeedDMS_Core_Role::role_guest); }

	function getRole() { return $this->_role; }

	function setRole($newrole) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblRoles` SET `role` = " . $newrole . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_role = $newrole;
		return true;
	} /* }}} */

	function getNoAccess() { return $this->_noaccess; }

	function setNoAccess($noaccess) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblRoles` SET `noaccess` = " . $db->qstr($noaccess ? implode(',',$noaccess) : '') . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_noaccess = $noaccess;
		return true;
	} /* }}} */

	/**
	 * Delete role
	 *
	 * @return boolean true on success or false in case of an error
	 */
	function remove() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "DELETE FROM `tblRoles` WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			return false;
		}

		return true;
	} /* }}} */

	function isUsed() { /* {{{ */
		$db = $this->_dms->getDB();
		
		$queryStr = "SELECT * FROM `tblUsers` WHERE `role`=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_array($resArr) && count($resArr) == 0)
			return false;
		return true;
	} /* }}} */

	function getUsers() { /* {{{ */
		$db = $this->_dms->getDB();
		
		if (!isset($this->_users)) {
			$queryStr = "SELECT * FROM `tblUsers` WHERE `role`=".$this->_id;
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_users = array();

			$classnamerole = $this->_dms->getClassname('role');

			$classname = $this->_dms->getClassname('user');
			foreach ($resArr as $row) {
				$role = $classnamerole::getInstance($row['role'], $this->_dms);
				$user = new $classname($row["id"], $row["login"], $row["pwd"], $row["fullName"], $row["email"], $row["language"], $row["theme"], $row["comment"], $role, $row['hidden']);
				$user->setDMS($this->_dms);
				array_push($this->_users, $user);
			}
		}
		return $this->_users;
	} /* }}} */

} /* }}} */

class SeedDMS_Core_RackLocations { /* {{{ */
	/**
	 * @var integer id of role
	 *
	 * @access protected
	 */
	var $_id;

	/**
	 * @var string name of role
	 *
	 * @access protected
	 */
	var $_kode;

	/**
	 * @var integer 
	 *
	 * @access protected
	 */
	var $_nomor;

	/**
	 * @var integer 
	 *
	 * @access protected
	 */
	var $_baris;

	/**
	 * @var string name of role
	 *
	 * @access protected
	 */
	var $_fisik;

	/**
	 * @var string name of role
	 *
	 * @access protected
	 */
	var $_keterangan;

	/**
	 * @var array list of status without access
	 *
	 * @access protected
	 */
	var $_noaccess;

	/**
	 * @var object reference to the dms instance this user belongs to
	 *
	 * @access protected
	 */
	var $_dms;

	const role_user = '0';
	const role_admin = '1';
	const role_guest = '2';

	function __construct($id, $kode, $nomor, $baris, $fisik, $keterangan, $noaccess=array()) { /* {{{ */
		$this->_id = $id;
		$this->_kode = $kode;
		$this->_nomor = $nomor;
		$this->_baris = $baris;
		$this->_fisik = $fisik;
		$this->_keterangan = $keterangan;
		$this->_noaccess = $noaccess;
		$this->_dms = null;
	} /* }}} */

	/**
	 * Create an instance of a role object
	 *
	 * @param string|integer $id Id, login name, or email of user, depending
	 * on the 3rd parameter.
	 * @param object $dms instance of dms
	 * @param string $by search by [name|email]. If 'name' is passed, the method
	 * will check for the 4th paramater and also filter by email. If this
	 * parameter is left empty, the user will be search by its Id.
	 * @param string $email optional email address if searching for name
	 * @return object instance of class SeedDMS_Core_User
	 */
	public static function getInstance($id, $dms, $by='') { /* {{{ */
		$db = $dms->getDB();

		switch($by) {
		case 'kode':
			$queryStr = "SELECT * FROM `tblRackLocations` WHERE `kode` = ".$db->qstr($id);
			break;
		default:
			$queryStr = "SELECT * FROM `tblRackLocations` WHERE id = " . (int) $id;
		}

		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		$resArr = $resArr[0];

		$racklocations = new self($resArr["id"], $resArr["kode"], $resArr["nomor"], $resArr["baris"], 
		$resArr["fisik"], $resArr["keterangan"], $resArr['noaccess'] ? explode(',', $resArr['noaccess']) : array());
		$racklocations->setDMS($dms);
		return $racklocations;
	} /* }}} */

	public static function getAllInstances($orderby, $dms) { /* {{{ */
		$db = $dms->getDB();

		if($orderby == 'kode')
			$queryStr = "SELECT * FROM `tblRackLocations` ORDER BY `name`";
		else
			$queryStr = "SELECT * FROM `tblRackLocations` ORDER BY `id`";
		$resArr = $db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		$allracklocations = array();

		for ($i = 0; $i < count($resArr); $i++) {
			$racklocations = new self($resArr[$i]["id"], $resArr[$i]["kode"], $resArr[$i]["nomor"], $resArr[$i]["baris"], $resArr[$i]["fisik"], $resArr[$i]["keterangan"], explode(',', $resArr[$i]['noaccess']));
			$racklocations->setDMS($dms);
			$allracklocations[$i] = $racklocations;
		}

		return $allracklocations;
} /* }}} */

	function setDMS($dms) {
		$this->_dms = $dms;
	}

	function getID() { return $this->_id; }

	function getKode() { return $this->_kode; }

	function setKode($newKode) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblRackLocations` SET `kode` =".$db->qstr($newKode)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_kode = $newKode;
		return true;
	} /* }}} */

	function getNomor() { return $this->_nomor; }

	function setNomor($newNomor) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblRackLocations` SET `nomor` =".$db->qstr($newNomor)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_nomor = $newNomor;
		return true;
	} /* }}} */

	function getBaris() { return $this->_baris; }

	function setBaris($newBaris) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblRackLocations` SET `baris` =".$db->qstr($newBaris)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_baris = $newBaris;
		return true;
	} /* }}} */

	function getFisik() { return $this->_fisik; }

	function setFisik($newFisik) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblRackLocations` SET `fisik` =".$db->qstr($newFisik)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_fisik = $newFisik;
		return true;
	} /* }}} */
	
	function getKeterangan() { return $this->_keterangan; }

	function setKeterangan($newKeterangan) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblRackLocations` SET `keterangan` =".$db->qstr($newKeterangan)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_keterangan = $newKeterangan;
		return true;
	} /* }}} */

	function isAdmin() { return ($this->_kode == SeedDMS_Core_RackLocations::role_admin); }

	function isGuest() { return ($this->_kode == SeedDMS_Core_RackLocations::role_guest); }

	function getRackLocations() { return $this->_kode; }

	function setRackLocations($newrole) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblRackLocations` SET `kode` = " . $newrole . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_kode = $newrole;
		return true;
	} /* }}} */

	function getNoAccess() { return $this->_noaccess; }

	function setNoAccess($noaccess) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblRackLocations` SET `noaccess` = " . $db->qstr($noaccess ? implode(',',$noaccess) : '') . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_noaccess = $noaccess;
		return true;
	} /* }}} */

	/**
	 * Delete role
	 *
	 * @return boolean true on success or false in case of an error
	 */
	function remove() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "DELETE FROM `tblRackLocations` WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			return false;
		}

		return true;
	} /* }}} */

	function isUsed() { /* {{{ */
		$db = $this->_dms->getDB();
		
		$queryStr = "SELECT * FROM `tblUsers` WHERE `role`=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_array($resArr) && count($resArr) == 0)
			return false;
		return true;
	} /* }}} */

	function getUsers() { /* {{{ */
		$db = $this->_dms->getDB();
		
		if (!isset($this->_users)) {
			$queryStr = "SELECT * FROM `tblUsers` WHERE `role`=".$this->_id;
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_users = array();

			$classnamerole = $this->_dms->getClassname('role');

			$classname = $this->_dms->getClassname('user');
			foreach ($resArr as $row) {
				$role = $classnamerole::getInstance($row['role'], $this->_dms);
				$user = new $classname($row["id"], $row["login"], $row["pwd"], $row["fullName"], $row["email"], $row["language"], $row["theme"], $row["comment"], $role, $row['hidden']);
				$user->setDMS($this->_dms);
				array_push($this->_users, $user);
			}
		}
		return $this->_users;
	} /* }}} */

} /* }}} */

/**
 * Class to represent a user in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Uwe Steinmann
 * @version    Release: 6.0.15
 */
class SeedDMS_Core_User { /* {{{ */
	/**
	 * @var integer id of user
	 *
	 * @access protected
	 */
	var $_id;

	/**
	 * @var string login name of user
	 *
	 * @access protected
	 */
	var $_login;

	/**
	 * @var string password of user as saved in database (md5)
	 *
	 * @access protected
	 */
	var $_pwd;

	/**
	 * @var string secret of user for 2-factor authentication
	 *
	 * @access protected
	 */
	var $_secret;

	/**
	 * @var string date when password expires
	 *
	 * @access protected
	 */
	var $_pwdExpiration;

	/**
	 * @var string full human readable name of user
	 *
	 * @access protected
	 */
	var $_fullName;

	/**
	 * @var string email address of user
	 *
	 * @access protected
	 */
	var $_email;

	/**
	 * @var string prefered language of user
	 *      possible values are subdirectories within the language directory
	 *
	 * @access protected
	 */
	var $_language;

	/**
	 * @var string preselected theme of user
	 *
	 * @access protected
	 */
	var $_theme;

	/**
	 * @var string comment of user
	 *
	 * @access protected
	 */
	var $_comment;

	/**
	 * @var string role of user. Can be one of SeedDMS_Core_User::role_user,
	 *      SeedDMS_Core_User::role_admin, SeedDMS_Core_User::role_guest
	 *
	 * @access protected
	 */
	var $_role;

	/**
	 * @var boolean true if user shall be hidden
	 *
	 * @access protected
	 */
	var $_isHidden;

	/**
	 * @var boolean true if user is disabled
	 *
	 * @access protected
	 */
	var $_isDisabled;

	/**
	 * @var int number of login failures
	 *
	 * @access protected
	 */
	var $_loginFailures;

	/**
	 * @var SeedDMS_Core_Folder home folder
	 *
	 * @access protected
	 */
	var $_homeFolder;

	/**
	 * @var array list of users this user can substitute
	 *
	 * @access protected
	 */
	var $_substitutes;

	/**
	 * @var array reverse list of users this user can substitute
	 *
	 * @access protected
	 */
	var $_rev_substitutes;

	/**
	 * @var SeedDMS_Core_DMS reference to the dms instance this user belongs to
	 *
	 * @access protected
	 */
	var $_dms;

	/**
	 * @var int
	 */
	private $_quota;

	/**
	 * @var bool
	 */
	private $_hasImage;

	const role_user = '0';
	const role_admin = '1';
	const role_guest = '2';

	/**
	 * SeedDMS_Core_User constructor.
	 * @param $id
	 * @param $login
	 * @param $pwd
	 * @param $fullName
	 * @param $email
	 * @param $language
	 * @param $theme
	 * @param $comment
	 * @param $role
	 * @param int $isHidden
	 * @param int $isDisabled
	 * @param string $pwdExpiration
	 * @param int $loginFailures
	 * @param int $quota
	 * @param null $homeFolder
	 * @param string $secret
	 */
	function __construct($id, $login, $pwd, $fullName, $email, $language, $theme, $comment, $role, $isHidden=0, $isDisabled=0, $pwdExpiration='', $loginFailures=0, $quota=0, $homeFolder=null, $secret='') {
		$this->_id = $id;
		$this->_login = $login;
		$this->_pwd = $pwd;
		$this->_fullName = $fullName;
		$this->_email = $email;
		$this->_language = $language;
		$this->_theme = $theme;
		$this->_comment = $comment;
		$this->_role = $role;
		$this->_isHidden = $isHidden;
		$this->_isDisabled = $isDisabled;
		$this->_pwdExpiration = $pwdExpiration;
		$this->_loginFailures = $loginFailures;
		$this->_quota = $quota;
		$this->_homeFolder = $homeFolder;
		$this->_secret = $secret;
		$this->_substitutes = null;
		$this->_rev_substitutes = null;
		$this->_dms = null;
	}

	/**
	 * Create an instance of a user object
	 *
	 * @param string|integer $id Id, login name, or email of user, depending
	 * on the 3rd parameter.
	 * @param SeedDMS_Core_DMS $dms instance of dms
	 * @param string $by search by [name|email]. If 'name' is passed, the method
	 * will check for the 4th paramater and also filter by email. If this
	 * parameter is left empty, the user will be search by its Id.
	 * @param string $email optional email address if searching for name
	 * @return SeedDMS_Core_User|bool instance of class SeedDMS_Core_User if user was
	 * found, null if user was not found, false in case of error
	 */
	public static function getInstance($id, $dms, $by='', $email='') { /* {{{ */
		$db = $dms->getDB();

		switch($by) {
		case 'name':
			$queryStr = "SELECT * FROM `tblUsers` WHERE `login` = ".$db->qstr($id);
			if($email)
				$queryStr .= " AND `email`=".$db->qstr($email);
			break;
		case 'email':
			$queryStr = "SELECT * FROM `tblUsers` WHERE `email` = ".$db->qstr($id);
			break;
		default:
			$queryStr = "SELECT * FROM `tblUsers` WHERE `id` = " . (int) $id;
		}
		$resArr = $db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return null;

		$resArr = $resArr[0];

		$classname = $dms->getClassname('role');
		$role = $classname::getInstance($resArr['role'], $dms);

		$user = new self((int) $resArr["id"], $resArr["login"], $resArr["pwd"], $resArr["fullName"], $resArr["email"], $resArr["language"], $resArr["theme"], $resArr["comment"], $role, $resArr["hidden"], $resArr["disabled"], $resArr["pwdExpiration"], $resArr["loginfailures"], $resArr["quota"], $resArr["homefolder"], $resArr["secret"]);
		$user->setDMS($dms);
		return $user;
	} /* }}} */

	/**
	 * @param $orderby
	 * @param SeedDMS_Core_DMS $dms
	 * @return SeedDMS_Core_User[]|bool
	 */
	public static function getAllInstances($orderby, $dms) { /* {{{ */
		$db = $dms->getDB();

		if($orderby == 'fullname')
			$queryStr = "SELECT * FROM `tblUsers` ORDER BY `fullName`";
		else
			$queryStr = "SELECT * FROM `tblUsers` ORDER BY `login`";
		$resArr = $db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		$users = array();

		$classname = $dms->getClassname('role');
		for ($i = 0; $i < count($resArr); $i++) {
		    /** @var SeedDMS_Core_User $user */
			$role = $classname::getInstance($resArr[$i]['role'], $dms);
			$user = new self($resArr[$i]["id"], $resArr[$i]["login"], $resArr[$i]["pwd"], $resArr[$i]["fullName"], $resArr[$i]["email"], (isset($resArr[$i]["language"])?$resArr[$i]["language"]:NULL), (isset($resArr[$i]["theme"])?$resArr[$i]["theme"]:NULL), $resArr[$i]["comment"], $role, $resArr[$i]["hidden"], $resArr[$i]["disabled"], $resArr[$i]["pwdExpiration"], $resArr[$i]["loginfailures"], $resArr[$i]["quota"], $resArr[$i]["homefolder"]);
			$user->setDMS($dms);
			$users[$i] = $user;
		}

		return $users;
} /* }}} */

	/**
	 * Check if this object is of type 'user'.
	 *
	 * @param string $type type of object
	 */
	public function isType($type) { /* {{{ */
		return $type == 'user';
	} /* }}} */

	/**
	 * @param SeedDMS_Core_DMS $dms
	 */
	function setDMS($dms) {
		$this->_dms = $dms;
	}

	/**
	 * @return SeedDMS_Core_DMS $dms
	 */
	function getDMS() {
		return $this->_dms;
	}

	/**
	 * @return int
	 */
	function getID() { return $this->_id; }

	/**
	 * @return string
	 */
	function getLogin() { return $this->_login; }

	/**
	 * @param $newLogin
	 * @return bool
	 */
	function setLogin($newLogin) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblUsers` SET `login` =".$db->qstr($newLogin)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_login = $newLogin;
		return true;
	} /* }}} */

	/**
	 * @return string
	 */
	function getFullName() { return $this->_fullName; }

	/**
	 * @param $newFullName
	 * @return bool
	 */
	function setFullName($newFullName) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblUsers` SET `fullName` = ".$db->qstr($newFullName)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_fullName = $newFullName;
		return true;
	} /* }}} */

	/**
	 * @return string
	 */
	function getPwd() { return $this->_pwd; }

	/**
	 * @param $newPwd
	 * @return bool
	 */
	function setPwd($newPwd) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblUsers` SET `pwd` =".$db->qstr($newPwd)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_pwd = $newPwd;
		return true;
	} /* }}} */

	/**
	 * @return string
	 */
	function getSecret() { return $this->_secret; }

	/**
	 * @param string $newSecret
	 */
	function setSecret($newSecret) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblUsers` SET `secret` =".$db->qstr($newSecret)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_secret = $newSecret;
		return true;
	} /* }}} */

	/**
	 * @return string
	 */
	function getPwdExpiration() { return $this->_pwdExpiration; }

	/**
	 * @param $newPwdExpiration
	 * @return bool
	 */
	function setPwdExpiration($newPwdExpiration) { /* {{{ */
		$db = $this->_dms->getDB();

		if(trim($newPwdExpiration) == '' || trim($newPwdExpiration) == 'never')
			$queryStr = "UPDATE `tblUsers` SET `pwdExpiration` = NULL WHERE `id` = " . $this->_id;
		elseif(trim($newPwdExpiration) == 'now')
			$queryStr = "UPDATE `tblUsers` SET `pwdExpiration` =".$db->qstr(date('Y-m-d H:i:s'))." WHERE `id` = " . $this->_id;
		else
			$queryStr = "UPDATE `tblUsers` SET `pwdExpiration` =".$db->qstr($newPwdExpiration)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_pwdExpiration = $newPwdExpiration;
		return true;
	} /* }}} */

	/**
	 * @return string
	 */
	function getEmail() { return $this->_email; }

	/**
	 * @param $newEmail
	 * @return bool
	 */
	function setEmail($newEmail) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblUsers` SET `email` =".$db->qstr($newEmail)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_email = $newEmail;
		return true;
	} /* }}} */

	/**
	 * @return string
	 */
	function getLanguage() { return $this->_language; }

	/**
	 * @param $newLanguage
	 * @return bool
	 */
	function setLanguage($newLanguage) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblUsers` SET `language` =".$db->qstr($newLanguage)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_language = $newLanguage;
		return true;
	} /* }}} */

	/**
	 * @return string
	 */
	function getTheme() { return $this->_theme; }

	/**
	 * @param string $newTheme
	 * @return bool
	 */
	function setTheme($newTheme) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblUsers` SET `theme` =".$db->qstr($newTheme)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_theme = $newTheme;
		return true;
	} /* }}} */

	/**
	 * @return string
	 */
	function getComment() { return $this->_comment; }

	/**
	 * @param $newComment
	 * @return bool
	 */
	function setComment($newComment) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblUsers` SET `comment` =".$db->qstr($newComment)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_comment = $newComment;
		return true;
	} /* }}} */

	/**
	 * @return string
	 */
	function getRole() { return $this->_role; }

	/**
	 * @param integer $newrole
	 * @return bool
	 */
	function setRole($newrole) { /* {{{ */
		$db = $this->_dms->getDB();

		if(is_object($newrole))
			$queryStr = "UPDATE `tblUsers` SET `role` = " . $newrole->getID() . " WHERE `id` = " . $this->_id;
		else
			$queryStr = "UPDATE `tblUsers` SET `role` = " . $newrole . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_role = $newrole;
		return true;
	} /* }}} */

	/**
	 * @return bool
	 */
	function isAdmin() { return (is_object($this->_role) ? $this->_role->isAdmin() : $this->_role == SeedDMS_Core_User::role_admin); }

	/**
	 * Was never used and is now deprecated
	 */
	function _setAdmin($isAdmin) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblUsers` SET `role` = " . SeedDMS_Core_User::role_admin . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_role = SeedDMS_Core_User::role_admin;
		return true;
	} /* }}} */

	/**
	 * @return bool
	 */
	function isGuest() { return (is_object($this->_role) ? $this->_role->isGuest() : $this->_role == SeedDMS_Core_User::role_guest); }

	/**
	 * Was never used and is now deprecated
	 */
	function _setGuest($isGuest) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblUsers` SET `role` = " . SeedDMS_Core_User::role_guest . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_role = SeedDMS_Core_User::role_guest;
		return true;
	} /* }}} */

	/**
	 * @return bool|int
	 */
	function isHidden() { return $this->_isHidden; }

	/**
	 * @param $isHidden
	 * @return bool
	 */
	function setHidden($isHidden) { /* {{{ */
		$db = $this->_dms->getDB();

		$isHidden = ($isHidden) ? "1" : "0";
		$queryStr = "UPDATE `tblUsers` SET `hidden` = " . $isHidden . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_isHidden = $isHidden;
		return true;
	}	 /* }}} */

	/**
	 * @return bool|int
	 */
	function isDisabled() { return $this->_isDisabled; }

	/**
	 * @param $isDisabled
	 * @return bool
	 */
	function setDisabled($isDisabled) { /* {{{ */
		$db = $this->_dms->getDB();

		$isDisabled = ($isDisabled) ? "1" : "0";
		$queryStr = "UPDATE `tblUsers` SET `disabled` = " . $isDisabled . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_isDisabled = $isDisabled;
		return true;
	}	 /* }}} */

	/**
	 * @return bool|int
	 */
	function addLoginFailure() { /* {{{ */
		$db = $this->_dms->getDB();

		$this->_loginFailures++;
		$queryStr = "UPDATE `tblUsers` SET `loginfailures` = " . $this->_loginFailures . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		return $this->_loginFailures;
	} /* }}} */

	/**
	 * @return bool
	 */
	function clearLoginFailures() { /* {{{ */
		$db = $this->_dms->getDB();

		$this->_loginFailures = 0;
		$queryStr = "UPDATE `tblUsers` SET `loginfailures` = " . $this->_loginFailures . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		return true;
	} /* }}} */

	/**
	 * Calculate the disk space for all documents owned by the user
	 * 
	 * This is done by using the internal database field storing the
	 * filesize of a document version.
	 *
	 * @return integer total disk space in Bytes
	 */
	function getUsedDiskSpace() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT SUM(`fileSize`) sum FROM `tblDocumentContent` a LEFT JOIN `tblDocuments` b ON a.`document`=b.`id` WHERE b.`owner` = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		return $resArr[0]['sum'];
	} /* }}} */

	/**
	 * @return int
	 */
	function getQuota() { return $this->_quota; }

	/**
	 * @param integer $quota
	 * @return bool
	 */
	function setQuota($quota) { /* {{{ */
		$db = $this->_dms->getDB();

		$quota = intval($quota);
		$queryStr = "UPDATE `tblUsers` SET `quota` = " . $quota . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_quota = $quota;
		return true;
	}	 /* }}} */

	/**
	 * @return null|SeedDMS_Core_Folder
	 */
	function getHomeFolder() { return $this->_homeFolder; }

	/**
	 * @param integer $homefolder
	 * @return bool
	 */
	function setHomeFolder($homefolder) { /* {{{ */
		$db = $this->_dms->getDB();
		$homefolder = intval($homefolder);

		$queryStr = "UPDATE `tblUsers` SET `homefolder` = " . ($homefolder ? $homefolder : 'NULL') . " WHERE `id` = " . $this->_id;
		echo $queryStr;
		if (!$db->getResult($queryStr))
			return false;

		$this->_homeFolder = $homefolder;
		return true;
	}	 /* }}} */

	/**
	 * Remove user from all processes
	 *
	 * This method adds another log entry to the reviews, approvals, receptions, revisions,
	 * which indicates the user has been deleted from the process. By default it will
	 * do so for each review/approval/reception/revision regardless of its current state unless
	 * the user has been removed already (status=-2). So even
	 * reviews/approvals/receptions/revisions already processed by the user will be added the log
	 * entry. Only if the last log entry was a removal already, it will not be
	 * added a second time.
	 * This behaviour can be changed by passing a list of states in the optional
	 * argument $states. Only reviews, etc. in the given state will be affected.
	 * This allows to remove the user only if the review, etc. has not been done
	 * (state = 0).
	 *
	 * The last optional parameter $newuser is for replacing the user currently in
	 * charge by another user. 
	 *
	 * @param object $user the user doing the removal (needed for entry in
	 *        review and approve log).
	 * @param array $states remove user only from reviews/approvals in one of the states
	 *        e.g. if passing array('review'=>array(0)), the method will operate on
	 *        reviews which has not been touched yet.
	 * @param object user who is take over the processes
	 * @return boolean true on success or false in case of an error
	 */
	private function __removeFromProcesses($user, $states = array(), $newuser=null) { /* {{{ */
		$db = $this->_dms->getDB();

		/* Get a list of all reviews, even those of older document versions */
		$reviewStatus = $this->getReviewStatus();
		$db->startTransaction();
		foreach ($reviewStatus["indstatus"] as $ri) {
			if($ri['status'] != -2 && (!isset($states['review']) || in_array($ri['status'], $states['review']))) {
				$queryStr = "INSERT INTO `tblDocumentReviewLog` (`reviewID`, `status`, `comment`, `date`, `userID`) ".
					"VALUES ('". $ri["reviewID"] ."', '-2', 'Reviewer removed from process', ".$db->getCurrentDatetime().", '". $user->getID() ."')";
				$res=$db->getResult($queryStr);
				if(!$res) {
					$db->rollbackTransaction();
					return false;
				}
				/* Only reviews not done already can be transferred to a new user */
				if($newuser && $ri['status'] == 0) {
					if($doc = $this->_dms->getDocument($ri['documentID'])) {
						if($version = $doc->getContentByVersion($ri['version'])) {
							$ret = $version->addIndReviewer($newuser, $user);
							/* returns -3 if the user is already a reviewer */ 
							if($ret != 0 && $ret != -3) {
								$db->rollbackTransaction();
								return false;
							}
						}
					}
				}
			}
		}
		$db->commitTransaction();

		/* Get a list of all approvals, even those of older document versions */
		$approvalStatus = $this->getApprovalStatus();
		$db->startTransaction();
		foreach ($approvalStatus["indstatus"] as $ai) {
			if($ai['status'] != -2 && (!isset($states['approval']) || in_array($ai['status'], $states['approval']))) {
				$queryStr = "INSERT INTO `tblDocumentApproveLog` (`approveID`, `status`, `comment`, `date`, `userID`) ".
					"VALUES ('". $ai["approveID"] ."', '-2', 'Approver removed from process', ".$db->getCurrentDatetime().", '". $user->getID() ."')";
				$res=$db->getResult($queryStr);
				if(!$res) {
					$db->rollbackTransaction();
					return false;
				}
				/* Only approvals not done already can be transferred to a new user */
				if($newuser && $ai['status'] == 0) {
					if($doc = $this->_dms->getDocument($ai['documentID'])) {
						if($version = $doc->getContentByVersion($ai['version'])) {
							$ret = $version->addIndReviewer($newuser, $user);
							/* returns -3 if the user is already a reviewer */ 
							if($ret != 0 && $ret != -3) {
								$db->rollbackTransaction();
								return false;
							}
						}
					}
				}
			}
		}
		$db->commitTransaction();

		/* Get a list of all receptions, even those of older document versions */
		$receiptStatus = $this->getReceiptStatus();
		$db->startTransaction();
		foreach ($receiptStatus["indstatus"] as $ri) {
			if($ri['status'] != -2 && (!isset($states['receipt']) || in_array($ri['status'], $states['receipt']))) {
				$queryStr = "INSERT INTO `tblDocumentReceiptLog` (`receiptID`, `status`, `comment`, `date`, `userID`) ".
					"VALUES ('". $ri["receiptID"] ."', '-2', 'Recipient removed from process', ".$db->getCurrentDatetime().", '". $user->getID() ."')";
				$res=$db->getResult($queryStr);
				if(!$res) {
					$db->rollbackTransaction();
					return false;
				}
				/* Only receptions not done already can be transferred to a new user */
				if($newuser && $ri['status'] == 0) {
					if($doc = $this->_dms->getDocument($ri['documentID'])) {
						if($version = $doc->getContentByVersion($ri['version'])) {
							$ret = $version->addIndRecipient($newuser, $user);
							/* returns -3 if the user is already a recipient */ 
							if($ret != 0 && $ret != -3) {
								$db->rollbackTransaction();
								return false;
							}
						}
					}
				}
			}
		}
		$db->commitTransaction();

		/* Get a list of all revisions, even those of older document versions */
		$revisionStatus = $this->getRevisionStatus();
		$db->startTransaction();
		foreach ($revisionStatus["indstatus"] as $ri) {
			if($ri['status'] != -2 && (!isset($states['revision']) || in_array($ri['status'], $states['revision']))) {
				$queryStr = "INSERT INTO `tblDocumentRevisionLog` (`revisionID`, `status`, `comment`, `date`, `userID`) ".
					"VALUES ('". $ri["revisionID"] ."', '-2', 'Revisor removed from process', ".$db->getCurrentDatetime().", '". $user->getID() ."')";
				$res=$db->getResult($queryStr);
				if(!$res) {
					$db->rollbackTransaction();
					return false;
				}
				/* Only revisions not already done or sleeping can be transferred to a new user */
				if($newuser && in_array($ri['status'], array(S_LOG_WAITING, S_LOG_SLEEPING))) {
					if($doc = $this->_dms->getDocument($ri['documentID'])) {
						if($version = $doc->getContentByVersion($ri['version'])) {
							$ret = $version->addIndRevisor($newuser, $user);
							/* returns -3 if the user is already a revisor */ 
							if($ret != 0 && $ret != -3) {
								$db->rollbackTransaction();
								return false;
							}
						}
					}
				}
			}
		}
		$db->commitTransaction();

		return true;
	} /* }}} */

	/**
	 * Remove user from all processes
	 *
	 * This includes review, approval and workflow
	 *
	 * @param object $user the user doing the removal (needed for entry in
	 *        review and approve log).
	 * @param array $states remove user only from reviews/approvals in one of the states
	 * @return boolean true on success or false in case of an error
	 */
	public function removeFromProcesses($user, $states=array(), $newuser=null) { /* {{{ */
		$db = $this->_dms->getDB();

		$db->startTransaction();
		if(!$this->__removeFromProcesses($user, $states, $newuser)) {
			$db->rollbackTransaction();
			return false;
		}
		$db->commitTransaction();
		return true;
	} /* }}} */

	/**
	 * Transfer documents and folders to another user
	 *
	 * @param object $assignToUser the user who is new owner of folders and
	 *        documents which previously were owned by the delete user.
	 * @return boolean true on success or false in case of an error
	 */
	private function __transferDocumentsFolders($assignToUser) { /* {{{ */
		$db = $this->_dms->getDB();

		if(!$assignToUser)
			return false;

		/* Assign documents of the removed user to the given user */
		$queryStr = "UPDATE `tblFolders` SET `owner` = " . $assignToUser->getID() . " WHERE `owner` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			return false;
		}

		$queryStr = "UPDATE `tblDocuments` SET `owner` = " . $assignToUser->getID() . " WHERE `owner` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			return false;
		}

		$queryStr = "UPDATE `tblDocumentContent` SET `createdBy` = " . $assignToUser->getID() . " WHERE `createdBy` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			return false;
		}

		// ... but keep public links
		$queryStr = "UPDATE `tblDocumentLinks` SET `userID` = " . $assignToUser->getID() . " WHERE `userID` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			return false;
		}

		// set administrator for deleted user's attachments
		$queryStr = "UPDATE `tblDocumentFiles` SET `userID` = " . $assignToUser->getID() . " WHERE `userID` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			return false;
		}

		return true;
	} /* }}} */

	/**
	 * Transfer documents and folders to another user
	 *
	 * @param object $assignToUser the user who is new owner of folders and
	 *        documents which previously were owned by the delete user.
	 * @return boolean true on success or false in case of an error
	 */
	public function transferDocumentsFolders($assignToUser) { /* {{{ */
		$db = $this->_dms->getDB();

		if($assignToUser->getID() == $this->_id)
			return true;

		$db->startTransaction();
		if(!$this->__transferDocumentsFolders($assignToUser)) {
			$db->rollbackTransaction();
			return false;
		}
		$db->commitTransaction();
		return true;
	} /* }}} */

	/**
	 * Transfer events to another user
	 *
	 * @param object $assignToUser the user who is new owner of events
	 * @return boolean true on success or false in case of an error
	 */
	private function __transferEvents($assignToUser) { /* {{{ */
		$db = $this->_dms->getDB();

		if(!$assignToUser)
			return false;

		// set new owner of events
		$queryStr = "UPDATE `tblEvents` SET `userID` = " . $assignToUser->getID() . " WHERE `userID` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			return false;
		}

		return true;
	} /* }}} */

	/**
	 * Transfer events to another user
	 *
	 * @param object $assignToUser the user who is new owner of events
	 * @return boolean true on success or false in case of an error
	 */
	public function transferEvents($assignToUser) { /* {{{ */
		$db = $this->_dms->getDB();

		if($assignToUser->getID() == $this->_id)
			return true;

		$db->startTransaction();
		if(!$this->__transferEvents($assignToUser)) {
			$db->rollbackTransaction();
			return false;
		}
		$db->commitTransaction();
		return true;
	} /* }}} */

	/**
	 * Remove the user and also remove all its keywords, notifications, etc.
	 * Do not remove folders and documents of the user, but assign them
	 * to a different user.
	 *
	 * @param SeedDMS_Core_User $user the user doing the removal (needed for entry in
	 *        review and approve log).
	 * @param SeedDMS_Core_User $assignToUser the user who is new owner of folders and
	 *        documents which previously were owned by the delete user.
	 * @return boolean true on success or false in case of an error
	 */
	function remove($user, $assignToUser=null) { /* {{{ */
		$db = $this->_dms->getDB();

		/* Records like folders and documents that formely have belonged to
		 * the user will assign to another user. If no such user is set,
		 * the function now returns false and will not use the admin user
		 * anymore.
		 */
		if(!$assignToUser)
			return false;
			/** @noinspection PhpUnusedLocalVariableInspection */
			$assignTo = $assignToUser->getID();

		$db->startTransaction();

		// delete private keyword lists
		$queryStr = "SELECT `tblKeywords`.`id` FROM `tblKeywords`, `tblKeywordCategories` WHERE `tblKeywords`.`category` = `tblKeywordCategories`.`id` AND `tblKeywordCategories`.`owner` = " . $this->_id;
		$resultArr = $db->getResultArray($queryStr);
		if (count($resultArr) > 0) {
			$queryStr = "DELETE FROM `tblKeywords` WHERE ";
			for ($i = 0; $i < count($resultArr); $i++) {
				$queryStr .= "id = " . $resultArr[$i]["id"];
				if ($i + 1 < count($resultArr))
					$queryStr .= " OR ";
			}
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
		}

		$queryStr = "DELETE FROM `tblKeywordCategories` WHERE `owner` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		//Benachrichtigungen entfernen
		$queryStr = "DELETE FROM `tblNotify` WHERE `userID` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// Remove private links on documents ...
		$queryStr = "DELETE FROM `tblDocumentLinks` WHERE `userID` = " . $this->_id . " AND `public` = 0";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		/* Assign documents, folders, files, public document links of the removed user to the given user */
		if(!$this->__transferDocumentsFolders($assignToUser)) {
				$db->rollbackTransaction();
				return false;
		}

		// unlock documents locked by the user
		$queryStr = "DELETE FROM `tblDocumentLocks` WHERE `userID` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// Delete user from all groups
		$queryStr = "DELETE FROM `tblGroupMembers` WHERE `userID` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// User aus allen ACLs streichen
		$queryStr = "DELETE FROM `tblACLs` WHERE `userID` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// Delete image of user
		$queryStr = "DELETE FROM `tblUserImages` WHERE `userID` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// Delete entries in password history
		$queryStr = "DELETE FROM `tblUserPasswordHistory` WHERE `userID` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// Delete entries in password request
		$queryStr = "DELETE FROM `tblUserPasswordRequest` WHERE `userID` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// mandatory review/approve
		$queryStr = "DELETE FROM `tblMandatoryReviewers` WHERE `reviewerUserID` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM `tblMandatoryApprovers` WHERE `approverUserID` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM `tblMandatoryReviewers` WHERE `userID` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM `tblMandatoryApprovers` WHERE `userID` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM `tblWorkflowMandatoryWorkflow` WHERE `userid` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM `tblWorkflowTransitionUsers` WHERE `userid` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		/* Assign events of the removed user to the given user */
		if(!$this->__transferEvents($assignToUser)) {
				$db->rollbackTransaction();
				return false;
		}

		// Delete user itself
		$queryStr = "DELETE FROM `tblUsers` WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// TODO : update document status if reviewer/approver has been deleted
		// "DELETE FROM `tblDocumentApproveLog` WHERE `userID` = " . $this->_id;
		// "DELETE FROM `tblDocumentReviewLog` WHERE `userID` = " . $this->_id;

		if(!$this->__removeFromProcesses($user)) {
				$db->rollbackTransaction();
				return false;
		}

		$db->commitTransaction();
		return true;
	} /* }}} */

	/**
	 * Make the user a member of a group
	 * This function uses {@link SeedDMS_Group::addUser} but checks before if
	 * the user is already a member of the group.
	 *
	 * @param SeedDMS_Core_Group $group group to be the member of
	 * @return boolean true on success or false in case of an error or the user
	 *        is already a member of the group
	 */
	function joinGroup($group) { /* {{{ */
		if ($group->isMember($this))
			return false;

		if (!$group->addUser($this))
			return false;

		unset($this->_groups);
		return true;
	} /* }}} */

	/**
	 * Removes the user from a group
	 * This function uses {@link SeedDMS_Group::removeUser} but checks before if
	 * the user is a member of the group at all.
	 *
	 * @param SeedDMS_Core_Group $group group to leave
	 * @return boolean true on success or false in case of an error or the user
	 *        is not a member of the group
	 */
	function leaveGroup($group) { /* {{{ */
		if (!$group->isMember($this))
			return false;

		if (!$group->removeUser($this))
			return false;

		unset($this->_groups);
		return true;
	} /* }}} */

	/**
	 * Get all groups the user is a member of
	 *
	 * @return SeedDMS_Core_Group[]|bool list of groups
	 */
	function getGroups() { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_groups))
		{
			$queryStr = "SELECT `tblGroups`.*, `tblGroupMembers`.`userID` FROM `tblGroups` ".
				"LEFT JOIN `tblGroupMembers` ON `tblGroups`.`id` = `tblGroupMembers`.`groupID` ".
				"WHERE `tblGroupMembers`.`userID`='". $this->_id ."'";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_groups = array();
			$classname = $this->_dms->getClassname('group');
			foreach ($resArr as $row) {
				/** @var SeedDMS_Core_Group $group */
				$group = new $classname((int) $row["id"], $row["name"], $row["comment"]);
				$group->setDMS($this->_dms);
				array_push($this->_groups, $group);
			}
		}
		return $this->_groups;
	} /* }}} */

	function getWorkLocations() { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_worklocations))
		{
			$queryStr = "SELECT `tblWorkLocations`.*, `tblWorkMembers`.`userID` FROM `tblWorkLocations` ".
				"LEFT JOIN `tblWorkMembers` ON `tblWorkLocations`.`id` = `tblWorkMembers`.`worklocationID` ".
				"WHERE `tblWorkMembers`.`userID`='". $this->_id ."'";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_worklocations = array();
			$classname = $this->_dms->getClassname('worklocation');
			foreach ($resArr as $row) {
				/** @var SeedDMS_Core_WorkLocation $worklocation */
				$worklocation = new $classname((int) $row["id"], $row["name"], $row["comment"]);
				$worklocation->setDMS($this->_dms);
				array_push($this->_worklocations, $worklocation);
			}
		}
		return $this->_worklocations;
	} /* }}} */

	/**
	 * Checks if user is member of a given group
	 *
	 * @param SeedDMS_Core_Group $group
	 * @return boolean true if user is member of the given group otherwise false
	 */
	function isMemberOfGroup($group) { /* {{{ */
		return $group->isMember($this);
	} /* }}} */

	function isMemberOfWorkLocation($worklocation) { /* {{{ */
		return $worklocation->isMember($this);
	} /* }}} */

	/**
	 * Check if user has an image in its profile
	 *
	 * @return boolean true if user has a picture of itself
	 */
	function hasImage() { /* {{{ */
		if (!isset($this->_hasImage)) {
			$db = $this->_dms->getDB();

			$queryStr = "SELECT COUNT(*) AS num FROM `tblUserImages` WHERE `userID` = " . $this->_id;
			$resArr = $db->getResultArray($queryStr);
			if ($resArr === false)
				return false;

			if ($resArr[0]["num"] == 0)	$this->_hasImage = false;
			else $this->_hasImage = true;
		}

		return $this->_hasImage;
	} /* }}} */

	/**
	 * Get the image from the users profile
	 *
	 * @return array|bool image data
	 */
	function getImage() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM `tblUserImages` WHERE `userID` = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if ($resArr === false)
			return false;

		if($resArr)
			$resArr = $resArr[0];
		return $resArr;
	} /* }}} */

	/**
	 * @param $tmpfile
	 * @param $mimeType
	 * @return bool
	 */
	function setImage($tmpfile, $mimeType) { /* {{{ */
		$db = $this->_dms->getDB();

		$fp = fopen($tmpfile, "rb");
		if (!$fp) return false;
		$content = fread($fp, filesize($tmpfile));
		fclose($fp);

		if ($this->hasImage())
			$queryStr = "UPDATE `tblUserImages` SET `image` = '".base64_encode($content)."', `mimeType` = ".$db->qstr($mimeType)." WHERE `userID` = " . $this->_id;
		else
			$queryStr = "INSERT INTO `tblUserImages` (`userID`, `image`, `mimeType`) VALUES (" . $this->_id . ", '".base64_encode($content)."', ".$db->qstr($mimeType).")";
		if (!$db->getResult($queryStr))
			return false;

		$this->_hasImage = true;
		return true;
	} /* }}} */

	/**
	 * Returns all documents of a given user
	 * @return SeedDMS_Core_Document[]|bool list of documents
	 */
	function getDocuments() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lockUser` ".
			"FROM `tblDocuments` ".
			"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
			"WHERE `tblDocuments`.`owner` = " . $this->_id . " ORDER BY `sequence`";

		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$documents = array();
		$classname = $this->_dms->getClassname('document');
		foreach ($resArr as $row) {
			/** @var SeedDMS_Core_Document $document */
			$document = new $classname((int) $row["id"], $row["name"], $row["comment"], $row["date"], $row["expires"], $row["owner"], $row["folder"], $row["inheritAccess"], $row["defaultAccess"], $row["lockUser"], $row["keywords"], $row["sequence"]);
			$document->setDMS($this->_dms);
			$documents[] = $document;
		}
		return $documents;
	} /* }}} */

	/**
	 * Returns all documents locked by a given user
	 *
	 * @return bool|SeedDMS_Core_Document[] list of documents
	 */
	function getDocumentsLocked() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lockUser` ".
			"FROM `tblDocumentLocks` LEFT JOIN `tblDocuments` ON `tblDocuments`.`id` = `tblDocumentLocks`.`document` ".
			"WHERE `tblDocumentLocks`.`userID` = '".$this->_id."' ".
			"ORDER BY `id` DESC";

		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$documents = array();
		$classname = $this->_dms->getClassname('document');
		foreach ($resArr as $row) {
			/** @var SeedDMS_Core_Document $document */
			$document = new $classname((int) $row["id"], $row["name"], $row["comment"], $row["date"], $row["expires"], $row["owner"], $row["folder"], $row["inheritAccess"], $row["defaultAccess"], $row["lockUser"], $row["keywords"], $row["sequence"]);
			$document->setDMS($this->_dms);
			$documents[] = $document;
		}
		return $documents;
	} /* }}} */

	/**
	 * Returns all documents check out by a given user
	 *
	 * @param object $user
	 * @return array list of documents
	 */
	function getDocumentsCheckOut() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT `tblDocuments`.* ".
			"FROM `tblDocumentCheckOuts` LEFT JOIN `tblDocuments` ON `tblDocuments`.`id` = `tblDocumentCheckOuts`.`document` ".
			"WHERE `tblDocumentCheckOuts`.`userID` = '".$this->_id."' ".
			"ORDER BY `id` ASC";

		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$documents = array();
		$classname = $this->_dms->getClassname('document');
		foreach ($resArr as $row) {
			$document = new $classname($row["id"], $row["name"], $row["comment"], $row["date"], $row["expires"], $row["owner"], $row["folder"], $row["inheritAccess"], $row["defaultAccess"], $row["lockUser"], $row["keywords"], $row["sequence"]);
			$document->setDMS($this->_dms);
			$documents[] = $document;
		}
		return $documents;
	} /* }}} */

	/**
	 * Get a list of reviews
	 *
	 * This function returns a list of all reviews and their latest log entry
	 * seperated by individuals and groups. If the document id
	 * is passed, then only this document will be checked for reviews. The
	 * same is true for the version of a document which limits the list
	 * further. If you do not limit on a version it will retrieve the status
	 * for each version, that includes even older versions which has been superseded
	 * by a new version.
	 *
	 * For a detailed description of the result array see
	 * {link SeedDMS_Core_User::getApprovalStatus} which does the same for
	 * approvals.
	 *
	 * @param int $documentID optional document id for which to retrieve the
	 *        reviews
	 * @param int $version optional version of the document
	 * @return array|bool list of all reviews
	 */
	function getReviewStatus($documentID=null, $version=null) { /* {{{ */
		$db = $this->_dms->getDB();

		$status = array("indstatus"=>array(), "grpstatus"=>array());

		// See if the user is assigned as an individual reviewer.
		/* FIXME: See query of getRevisionStatus() because it also restricts on
		 * latest version. This may be needed here too.
		 */
		$queryStr = "SELECT `tblDocumentReviewers`.*, `tblDocumentReviewLog`.`status`, ".
			"`tblDocumentReviewLog`.`comment`, `tblDocumentReviewLog`.`date`, ".
			"`tblDocumentReviewLog`.`userID` ".
			"FROM `tblDocumentReviewers` ".
			"LEFT JOIN `tblDocumentReviewLog` USING (`reviewID`) ".
			"WHERE `tblDocumentReviewers`.`type`='0' ".
			($documentID==null ? "" : "AND `tblDocumentReviewers`.`documentID` = '". (int) $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentReviewers`.`version` = '". (int) $version ."' ").
			"AND `tblDocumentReviewers`.`required`='". $this->_id ."' ".
			"ORDER BY `tblDocumentReviewLog`.`reviewLogID` DESC";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr === false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res) {
				if(isset($status["indstatus"][$res['documentID']])) {
					if($status["indstatus"][$res['documentID']]['date'] < $res['date']) {
						$status["indstatus"][$res['documentID']] = $res;
					}
				} else {
					$status["indstatus"][$res['documentID']] = $res;
				}
			}
		}

		// See if the user is the member of a group that has been assigned to
		// review the document version.
		$queryStr = "SELECT `tblDocumentReviewers`.*, `tblDocumentReviewLog`.`status`, ".
			"`tblDocumentReviewLog`.`comment`, `tblDocumentReviewLog`.`date`, ".
			"`tblDocumentReviewLog`.`userID` ".
			"FROM `tblDocumentReviewers` ".
			"LEFT JOIN `tblDocumentReviewLog` USING (`reviewID`) ".
			"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`groupID` = `tblDocumentReviewers`.`required` ".
			"WHERE `tblDocumentReviewers`.`type`='1' ".
			($documentID==null ? "" : "AND `tblDocumentReviewers`.`documentID` = '". (int) $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentReviewers`.`version` = '". (int) $version ."' ").
			"AND `tblGroupMembers`.`userID`='". $this->_id ."' ".
			"ORDER BY `tblDocumentReviewLog`.`reviewLogID` DESC";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr === false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res) {
				if(isset($status["grpstatus"][$res['documentID']])) {
					if($status["grpstatus"][$res['documentID']]['date'] < $res['date']) {
						$status["grpstatus"][$res['documentID']] = $res;
					}
				} else {
					$status["grpstatus"][$res['documentID']] = $res;
				}
			}
		}
		return $status;
	} /* }}} */

	/**
	 * Get a list of approvals
	 *
	 * This function returns a list of all approvals and their latest log entry
	 * seperated by individuals and groups. If the document id
	 * is passed, then only this document will be checked for approvals. The
	 * same is true for the version of a document which limits the list
	 * further. If you do not limit on a version it will retrieve the status
	 * for each version, that includes even older versions which has been superseded
	 * by a new version.
	 *
	 * The result array has two elements:
	 * - indstatus: which contains the approvals by individuals (users)
	 * - grpstatus: which contains the approvals by groups
	 *
	 * Each element is itself an array of approvals with the following elements
	 * (it is a combination of fields from tblDocumentApprovers and tblDocumentApproveLog):
	 * - approveID: unique id of approval
	 * - documentID: id of document, that needs to be approved
	 * - version: version of document, that needs to be approved
	 * - type: 0 for individual approval, 1 for group approval
	 * - required: id of user who is required to do the approval
	 * - status: 0 not approved, ....
	 * - comment: comment given during approval
	 * - date: date of approval
	 * - userID: id of user who has done the approval
	 *
	 * @param int $documentID optional document id for which to retrieve the
	 *        approvals
	 * @param int $version optional version of the document
	 * @return array|bool list of all approvals
	 */
	function getApprovalStatus($documentID=null, $version=null) { /* {{{ */
		$db = $this->_dms->getDB();

		$status = array("indstatus"=>array(), "grpstatus"=>array());
		/* FIXME: See query of getRevisionStatus() because it also restricts on
		 * latest version. This may be needed here too.
		 */
		$queryStr =
			"SELECT `tblDocumentApprovers`.*, `tblDocumentApproveLog`.`status`, ".
			"`tblDocumentApproveLog`.`comment`, `tblDocumentApproveLog`.`date`, ".
			"`tblDocumentApproveLog`.`userID` ".
			"FROM `tblDocumentApprovers` ".
			"LEFT JOIN `tblDocumentApproveLog` USING (`approveID`) ".
			"WHERE `tblDocumentApprovers`.`type`='0' ".
			($documentID==null ? "" : "AND `tblDocumentApprovers`.`documentID` = '". (int) $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentApprovers`.`version` = '". (int) $version ."' ").
			"AND `tblDocumentApprovers`.`required`='". $this->_id ."' ".
			"ORDER BY `tblDocumentApproveLog`.`approveLogID` DESC";

		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res) {
				if(isset($status["indstatus"][$res['documentID']])) {
					if($status["indstatus"][$res['documentID']]['date'] < $res['date']) {
						$status["indstatus"][$res['documentID']] = $res;
					}
				} else {
					$status["indstatus"][$res['documentID']] = $res;
				}
			}
		}

		// See if the user is the member of a group that has been assigned to
		// approve the document version.
		$queryStr =
			"SELECT `tblDocumentApprovers`.*, `tblDocumentApproveLog`.`status`, ".
			"`tblDocumentApproveLog`.`comment`, `tblDocumentApproveLog`.`date`, ".
			"`tblDocumentApproveLog`.`userID` ".
			"FROM `tblDocumentApprovers` ".
			"LEFT JOIN `tblDocumentApproveLog` USING (`approveID`) ".
			"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`groupID` = `tblDocumentApprovers`.`required` ".
			"WHERE `tblDocumentApprovers`.`type`='1' ".
			($documentID==null ? "" : "AND `tblDocumentApprovers`.`documentID` = '". (int) $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentApprovers`.`version` = '". (int) $version ."' ").
			"AND `tblGroupMembers`.`userID`='". $this->_id ."' ".
			"ORDER BY `tblDocumentApproveLog`.`approveLogID` DESC";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res) {
				if(isset($status["grpstatus"][$res['documentID']])) {
					if($status["grpstatus"][$res['documentID']]['date'] < $res['date']) {
						$status["grpstatus"][$res['documentID']] = $res;
					}
				} else {
					$status["grpstatus"][$res['documentID']] = $res;
				}
			}
		}
		return $status;
	} /* }}} */

	/**
	 * Get a list of receipts
	 * This function returns a list of all receipts seperated by individual
	 * and group receipts. If the document id
	 * is passed, then only this document will be checked for receipts. The
	 * same is true for the version of a document which limits the list
	 * further.
	 *
	 * For a detaile description of the result array see
	 * {link SeedDMS_Core_User::getApprovalStatus} which does the same for
	 * approvals.
	 *
	 * @param int $documentID optional document id for which to retrieve the
	 *        receipt
	 * @param int $version optional version of the document
	 * @return array list of all receipts
	 */
	function getReceiptStatus($documentID=null, $version=null) { /* {{{ */
		$db = $this->_dms->getDB();

		$status = array("indstatus"=>array(), "grpstatus"=>array());

		if (!$db->createTemporaryTable("ttcontentid")) {
			return false;
		}
		// See if the user is assigned as an individual recipient.
		// left join with ttcontentid to restrict result on latest version of document
		// unless a document and version is given
		$queryStr = "SELECT `tblDocumentRecipients`.*, `tblDocumentReceiptLog`.`status`, ".
			"`tblDocumentReceiptLog`.`comment`, `tblDocumentReceiptLog`.`date`, ".
			"`tblDocumentReceiptLog`.`userID` ".
			"FROM `tblDocumentRecipients` ".
			"LEFT JOIN `tblDocumentReceiptLog` USING (`receiptID`) ".
			"LEFT JOIN `ttcontentid` ON `ttcontentid`.`maxVersion` = `tblDocumentRecipients`.`version` AND `ttcontentid`.`document` = `tblDocumentRecipients`.`documentID` ".
			"WHERE `tblDocumentRecipients`.`type`='0' ".
			($documentID==null ? "" : "AND `tblDocumentRecipients`.`documentID` = '". (int) $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentRecipients`.`version` = '". (int) $version ."' ").
			($documentID==null && $version==null ? "AND `ttcontentid`.`maxVersion` = `tblDocumentRecipients`.`version` " : "").
			"AND `tblDocumentRecipients`.`required`='". $this->_id ."' ".
			"ORDER BY `tblDocumentReceiptLog`.`receiptLogID` DESC";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr === false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res) {
				if(isset($status["indstatus"][$res['documentID']])) {
					if($status["indstatus"][$res['documentID']]['date'] < $res['date']) {
						$status["indstatus"][$res['documentID']] = $res;
					}
				} else {
					$status["indstatus"][$res['documentID']] = $res;
				}
			}
		}

		// See if the user is the member of a group that has been assigned to
		// receipt the document version.
		$queryStr = "SELECT `tblDocumentRecipients`.*, `tblDocumentReceiptLog`.`status`, ".
			"`tblDocumentReceiptLog`.`comment`, `tblDocumentReceiptLog`.`date`, ".
			"`tblDocumentReceiptLog`.`userID` ".
			"FROM `tblDocumentRecipients` ".
			"LEFT JOIN `tblDocumentReceiptLog` USING (`receiptID`) ".
			"LEFT JOIN `ttcontentid` ON `ttcontentid`.`maxVersion` = `tblDocumentRecipients`.`version` AND `ttcontentid`.`document` = `tblDocumentRecipients`.`documentID` ".
			"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`groupID` = `tblDocumentRecipients`.`required` ".
			"WHERE `tblDocumentRecipients`.`type`='1' ".
			($documentID==null ? "" : "AND `tblDocumentRecipients`.`documentID` = '". (int) $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentRecipients`.`version` = '". (int) $version ."' ").
			($documentID==null && $version==null ? "AND `ttcontentid`.`maxVersion` = `tblDocumentRecipients`.`version` " : "").
			"AND `tblGroupMembers`.`userID`='". $this->_id ."' ".
			"ORDER BY `tblDocumentReceiptLog`.`receiptLogID` DESC";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr === false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res) {
				if(isset($status["grpstatus"][$res['documentID']])) {
					if($status["grpstatus"][$res['documentID']]['date'] < $res['date']) {
						$status["grpstatus"][$res['documentID']] = $res;
					}
				} else {
					$status["grpstatus"][$res['documentID']] = $res;
				}
			}
		}
		return $status;
	} /* }}} */

	/**
	 * Get a list of revisions
	 * This function returns a list of all revisions seperated by individual
	 * and group revisions. If the document id
	 * is passed, then only this document will be checked for revisions. The
	 * same is true for the version of a document which limits the list
	 * further.
	 *
	 * For a detaile description of the result array see
	 * {link SeedDMS_Core_User::getApprovalStatus} which does the same for
	 * approvals.
	 *
	 * @param int $documentID optional document id for which to retrieve the
	 *        revisions
	 * @param int $version optional version of the document
	 * @return array list of all revisions. If the result array has no elements,
	 * then the user was not a revisor. If there elements for 'indstatus' or
	 * 'grpstatus' then the revision hasn't been started.
	 */
	function getRevisionStatus($documentID=null, $version=null) { /* {{{ */
		$db = $this->_dms->getDB();

		$status = array("indstatus"=>array(), "grpstatus"=>array());

		if (!$db->createTemporaryTable("ttcontentid")) {
			return false;
		}
		// See if the user is assigned as an individual revisor.
		// left join with ttcontentid to restrict result on latest version of document
		// unless a document and version is given
		$queryStr = "SELECT `tblDocumentRevisors`.*, `tblDocumentRevisionLog`.`status`, ".
			"`tblDocumentRevisionLog`.`comment`, `tblDocumentRevisionLog`.`date`, ".
			"`tblDocumentRevisionLog`.`userID` ".
			"FROM `tblDocumentRevisors` ".
			"LEFT JOIN `tblDocumentRevisionLog` USING (`revisionID`) ".
			"LEFT JOIN `ttcontentid` ON `ttcontentid`.`maxVersion` = `tblDocumentRevisors`.`version` AND `ttcontentid`.`document` = `tblDocumentRevisors`.`documentID` ".
			"WHERE `tblDocumentRevisors`.`type`='0' ".
			($documentID==null ? "" : "AND `tblDocumentRevisors`.`documentID` = '". (int) $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentRevisors`.`version` = '". (int) $version ."' ").
			($documentID==null && $version==null ? "AND `ttcontentid`.`maxVersion` = `tblDocumentRevisors`.`version` " : "").
			"AND `tblDocumentRevisors`.`required`='". $this->_id ."' ".
			"ORDER BY `tblDocumentRevisionLog`.`revisionLogID` DESC";
		$resArr = $db->getResultArray($queryStr);
		if ($resArr === false)
			return false;
		if (count($resArr)>0) {
			$status['indstatus'] = array();
			foreach ($resArr as $res) {
				if($res['date']) {
					if(isset($status["indstatus"][$res['documentID']])) {
						if($status["indstatus"][$res['documentID']]['date'] < $res['date']) {
							$status["indstatus"][$res['documentID']] = $res;
						}
					} else {
						$status["indstatus"][$res['documentID']] = $res;
					}
				}
			}
		}

		// See if the user is the member of a group that has been assigned to
		// revision the document version.
		$queryStr = "SELECT `tblDocumentRevisors`.*, `tblDocumentRevisionLog`.`status`, ".
			"`tblDocumentRevisionLog`.`comment`, `tblDocumentRevisionLog`.`date`, ".
			"`tblDocumentRevisionLog`.`userID` ".
			"FROM `tblDocumentRevisors` ".
			"LEFT JOIN `tblDocumentRevisionLog` USING (`revisionID`) ".
			"LEFT JOIN `ttcontentid` ON `ttcontentid`.`maxVersion` = `tblDocumentRevisors`.`version` AND `ttcontentid`.`document` = `tblDocumentRevisors`.`documentID` ".
			"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`groupID` = `tblDocumentRevisors`.`required` ".
			"WHERE `tblDocumentRevisors`.`type`='1' ".
			($documentID==null ? "" : "AND `tblDocumentRevisors`.`documentID` = '". (int) $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentRevisors`.`version` = '". (int) $version ."' ").
			($documentID==null && $version==null ? "AND `ttcontentid`.`maxVersion` = `tblDocumentRevisors`.`version` " : "").
			"AND `tblGroupMembers`.`userID`='". $this->_id ."' ".
			"ORDER BY `tblDocumentRevisionLog`.`revisionLogID` DESC";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr === false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res) {
				if(isset($status["grpstatus"][$res['documentID']])) {
					if($status["grpstatus"][$res['documentID']]['date'] < $res['date']) {
						$status["grpstatus"][$res['documentID']] = $res;
					}
				} else {
					$status["grpstatus"][$res['documentID']] = $res;
				}
			}
		}
		return $status;
	} /* }}} */

	/**
	 * Get a list of documents with a workflow
	 *
	 * @param int $documentID optional document id for which to retrieve the
	 *        reviews
	 * @param int $version optional version of the document
	 * @return array|bool list of all workflows
	 */
	function getWorkflowStatus($documentID=null, $version=null) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = 'SELECT DISTINCT d.*, c.`userid` FROM `tblWorkflowTransitions` a LEFT JOIN `tblWorkflows` b ON a.`workflow`=b.`id` LEFT JOIN `tblWorkflowTransitionUsers` c ON a.`id`=c.`transition` LEFT JOIN `tblWorkflowDocumentContent` d ON b.`id`=d.`workflow` WHERE d.`document` IS NOT NULL AND a.`state`=d.`state` AND c.`userid`='.$this->_id;
		if($documentID) {
			$queryStr .= ' AND d.`document`='.(int) $documentID;
			if($version)
				$queryStr .= ' AND d.`version`='.(int) $version;
		}
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		$result['u'] = array();
		if (count($resArr)>0) {
			foreach ($resArr as $res) {
				$result['u'][] = $res;
			}
		}

		$queryStr = 'select distinct d.*, c.`groupid` from `tblWorkflowTransitions` a left join `tblWorkflows` b on a.`workflow`=b.`id` left join `tblWorkflowTransitionGroups` c on a.`id`=c.`transition` left join `tblWorkflowDocumentContent` d on b.`id`=d.`workflow` left join `tblGroupMembers` e on c.`groupid` = e.`groupID` where d.`document` is not null and a.`state`=d.`state` and e.`userID`='.$this->_id;
		if($documentID) {
			$queryStr .= ' AND d.`document`='.(int) $documentID;
			if($version)
				$queryStr .= ' AND d.`version`='.(int) $version;
		}
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		$result['g'] = array();
		if (count($resArr)>0) {
			foreach ($resArr as $res) {
				$result['g'][] = $res;
			}
		}
		return $result;
	} /* }}} */

	/**
	 * Get a list of workflows this user is involved as in individual
	 *
	 * @return array|bool list of all workflows
	 */
	function getWorkflowsInvolved() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = 'SELECT DISTINCT b.*, c.`userid` FROM `tblWorkflowTransitions` a LEFT JOIN `tblWorkflows` b ON a.`workflow`=b.`id` LEFT JOIN `tblWorkflowTransitionUsers` c ON a.`id`=c.`transition` WHERE c.`userid`='.$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		$result = array();
		if (count($resArr)>0) {
			foreach ($resArr as $res) {
				$result[] = $this->_dms->getWorkflow((int) $res['id']);
			}
		}

		return $result;
	} /* }}} */

	/**
	 * Get a list of mandatory reviewers
	 * A user which isn't trusted completely may have assigned mandatory
	 * reviewers (both users and groups).
	 * Whenever the user inserts a new document the mandatory reviewers are
	 * filled in as reviewers.
	 *
	 * @return array list of arrays with two elements containing the user id
	 *         (reviewerUserID) and group id (reviewerGroupID) of the reviewer.
	 */
	function getMandatoryReviewers() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM `tblMandatoryReviewers` WHERE `userID` = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);

		return $resArr;
	} /* }}} */

	/**
	 * Get a list of mandatory approvers
	 * See {link SeedDMS_Core_User::getMandatoryReviewers}
	 *
	 * @return array list of arrays with two elements containing the user id
	 *         (approverUserID) and group id (approverGroupID) of the approver.
	 */
	function getMandatoryApprovers() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM `tblMandatoryApprovers` WHERE `userID` = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);

		return $resArr;
	} /* }}} */

	/**
	 * Get a list of users this user is a mandatory reviewer of
	 *
	 * This method is the reverse function of getMandatoryReviewers(). It returns
	 * those user where the current user is a mandatory reviewer.
	 *
	 * @return SeedDMS_Core_User[]|bool list of users where this user is a mandatory reviewer.
	 */
	function isMandatoryReviewerOf() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM `tblMandatoryReviewers` WHERE `reviewerUserID` = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr) return false;

		$users = array();
		foreach($resArr as $res) {
			$users[] = self::getInstance($res['userID'], $this->_dms);
		}

		return $users;
	} /* }}} */

	/**
	 * Get a list of users this user is a mandatory approver of
	 *
	 * This method is the reverse function of getMandatoryApprovers(). It returns
	 * those user where the current user is a mandatory approver.
	 *
	 * @return SeedDMS_Core_User[]|bool list of users where this user is a mandatory approver.
	 */
	function isMandatoryApproverOf() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM `tblMandatoryApprovers` WHERE `approverUserID` = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr) return false;

		$users = array();
		foreach($resArr as $res) {
			$users[] = self::getInstance($res['userID'], $this->_dms);
		}

		return $users;
	} /* }}} */

	/**
	 * Get the mandatory workflow
	 * A user which isn't trusted completely may have assigned mandatory
	 * workflow
	 * Whenever the user inserts a new document the mandatory workflow is
	 * filled in as the workflow.
	 *
	 * @return SeedDMS_Core_Workflow|bool workflow
	 */
	function getMandatoryWorkflow() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM `tblWorkflowMandatoryWorkflow` WHERE `userid` = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr) return false;

		if(!$resArr)
			return null;

		$workflow = $this->_dms->getWorkflow($resArr[0]['workflow']);
		return $workflow;
	} /* }}} */

	/**
	 * Get the mandatory workflows
	 * A user which isn't trusted completely may have assigned mandatory
	 * workflow
	 * Whenever the user inserts a new document the mandatory workflow is
	 * filled in as the workflow.
	 *
	 * @return SeedDMS_Core_Workflow[]|bool workflow
	 */
	function getMandatoryWorkflows() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM `tblWorkflowMandatoryWorkflow` WHERE `userid` = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr) return false;

		if(!$resArr)
			return null;

		$workflows = array();
		foreach($resArr as $res) {
			$workflows[] = $this->_dms->getWorkflow($res['workflow']);
		}
		return $workflows;
	} /* }}} */

	/**
	 * Set a mandatory reviewer
	 * This function sets a mandatory reviewer if it isn't already set.
	 *
	 * @param integer $id id of reviewer
	 * @param boolean $isgroup true if $id is a group
	 * @return boolean true on success, otherwise false
	 */
	function setMandatoryReviewer($id, $isgroup=false) { /* {{{ */
		$db = $this->_dms->getDB();
		$id = (int) $id;

		if ($isgroup){

			$queryStr = "SELECT * FROM `tblMandatoryReviewers` WHERE `userID` = " . $this->_id . " AND `reviewerGroupID` = " . $id;
			$resArr = $db->getResultArray($queryStr);
			if (count($resArr)!=0) return true;

			$queryStr = "INSERT INTO `tblMandatoryReviewers` (`userID`, `reviewerGroupID`) VALUES (" . $this->_id . ", " . $id .")";
			$resArr = $db->getResult($queryStr);
			if (is_bool($resArr) && !$resArr) return false;

		}else{

			$queryStr = "SELECT * FROM `tblMandatoryReviewers` WHERE `userID` = " . $this->_id . " AND `reviewerUserID` = " . $id;
			$resArr = $db->getResultArray($queryStr);
			if (count($resArr)!=0) return true;

			$queryStr = "INSERT INTO `tblMandatoryReviewers` (`userID`, `reviewerUserID`) VALUES (" . $this->_id . ", " . $id .")";
			$resArr = $db->getResult($queryStr);
			if (is_bool($resArr) && !$resArr) return false;
		}

		return false;
	} /* }}} */

	/**
	 * Set a mandatory approver
	 * This function sets a mandatory approver if it isn't already set.
	 *
	 * @param integer $id id of approver
	 * @param boolean $isgroup true if $id is a group
	 * @return boolean true on success, otherwise false
	 */
	function setMandatoryApprover($id, $isgroup=false) { /* {{{ */
		$db = $this->_dms->getDB();
		$id = (int) $id;

		if ($isgroup){

			$queryStr = "SELECT * FROM `tblMandatoryApprovers` WHERE `userID` = " . $this->_id . " AND `approverGroupID` = " . $id;
			$resArr = $db->getResultArray($queryStr);
			if (count($resArr)!=0) return true;

			$queryStr = "INSERT INTO `tblMandatoryApprovers` (`userID`, `approverGroupID`) VALUES (" . $this->_id . ", " . $id .")";
			$resArr = $db->getResult($queryStr);
			if (is_bool($resArr) && !$resArr) return false;

		}else{

			$queryStr = "SELECT * FROM `tblMandatoryApprovers` WHERE `userID` = " . $this->_id . " AND `approverUserID` = " . $id;
			$resArr = $db->getResultArray($queryStr);
			if (count($resArr)!=0) return true;

			$queryStr = "INSERT INTO `tblMandatoryApprovers` (`userID`, `approverUserID`) VALUES (" . $this->_id . ", " . $id .")";
			$resArr = $db->getResult($queryStr);
			if (is_bool($resArr) && !$resArr) return false;
		}

		return false;
	} /* }}} */

	/**
	 * Set a mandatory workflow
	 * This function sets a mandatory workflow if it isn't already set.
	 *
	 * @param object $workflow workflow
	 * @return boolean true on success, otherwise false
	 */
	function setMandatoryWorkflow($workflow) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM `tblWorkflowMandatoryWorkflow` WHERE `userid` = " . $this->_id . " AND `workflow` = " . (int) $workflow->getID();
		$resArr = $db->getResultArray($queryStr);
		if (count($resArr)!=0) return true;

		$queryStr = "INSERT INTO `tblWorkflowMandatoryWorkflow` (`userid`, `workflow`) VALUES (" . $this->_id . ", " . $workflow->getID() .")";
		$resArr = $db->getResult($queryStr);
		if (is_bool($resArr) && !$resArr) return false;

		return false;
	} /* }}} */

	/**
	 * Set a mandatory workflows
	 * This function sets a list of mandatory workflows.
	 *
	 * @param SeedDMS_Core_Workflow[] $workflows list of workflow objects
	 * @return boolean true on success, otherwise false
	 */
	function setMandatoryWorkflows($workflows) { /* {{{ */
		$db = $this->_dms->getDB();

		$db->startTransaction();
		$queryStr = "DELETE FROM `tblWorkflowMandatoryWorkflow` WHERE `userid` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		foreach($workflows as $workflow) {
			$queryStr = "INSERT INTO `tblWorkflowMandatoryWorkflow` (`userid`, `workflow`) VALUES (" . $this->_id . ", " . $workflow->getID() .")";
			$resArr = $db->getResult($queryStr);
			if (is_bool($resArr) && !$resArr) {
				$db->rollbackTransaction();
				return false;
			}
		}

		$db->commitTransaction();
		return true;
	} /* }}} */

	/**
	 * Deletes all mandatory reviewers
	 *
	 * @return boolean true on success, otherwise false
	 */
	function delMandatoryReviewers() { /* {{{ */
		$db = $this->_dms->getDB();
		$queryStr = "DELETE FROM `tblMandatoryReviewers` WHERE `userID` = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;
		return true;
	} /* }}} */

	/**
	 * Deletes all mandatory approvers
	 *
	 * @return boolean true on success, otherwise false
	 */
	function delMandatoryApprovers() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "DELETE FROM `tblMandatoryApprovers` WHERE `userID` = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;
		return true;
	} /* }}} */

	/**
	 * Deletes the  mandatory workflow
	 *
	 * @return boolean true on success, otherwise false
	 */
	function delMandatoryWorkflow() { /* {{{ */
		$db = $this->_dms->getDB();
		$queryStr = "DELETE FROM `tblWorkflowMandatoryWorkflow` WHERE `userid` = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;
		return true;
	} /* }}} */

	/**
	 * Get all substitutes of the user
	 *
	 * These users are substitutes of the current user
	 *
	 * @return array list of users
	 */
	function getSubstitutes() { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_substitutes)) {
			$queryStr = "SELECT `tblUsers`.* FROM `tblUserSubstitutes` ".
				"LEFT JOIN `tblUsers` ON `tblUserSubstitutes`.`substitute` = `tblUsers`.`id` ".
				"WHERE `tblUserSubstitutes`.`user`='". $this->_id ."'";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_substitutes = array();
			$classname = $this->_dms->getClassname('user');
			foreach ($resArr as $row) {
				$user = new $classname($row["id"], $row["login"], $row["pwd"], $row["fullName"], $row["email"], $row["language"], $row["theme"], $row["comment"], $row["role"], $row["hidden"], $row["disabled"], $row["pwdExpiration"], $row["loginfailures"], $row["quota"], $row["homefolder"]);
				$user->setDMS($this->_dms);
				array_push($this->_substitutes, $user);
			}
		}
		return $this->_substitutes;
	} /* }}} */

	/**
	 * Get all users this user is a substitute of
	 *
	 * @return array list of users
	 */
	function getReverseSubstitutes() { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_rev_substitutes)) {
			$queryStr = "SELECT `tblUsers`.* FROM `tblUserSubstitutes` ".
				"LEFT JOIN `tblUsers` ON `tblUserSubstitutes`.`user` = `tblUsers`.`id` ".
				"LEFT JOIN `tblRoles` ON `tblRoles`.`id`=`tblUsers`.`role` ".
				"WHERE `tblUserSubstitutes`.`substitute`='". $this->_id ."'";
			/* None admins can only be substitutes for regular users, otherwise
			 * regular users can become admin
			 */
			if(!$this->isAdmin())
				$queryStr .= " AND `tblRoles`.`role` = ".SeedDMS_Core_User::role_user;
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_rev_substitutes = array();
			$classnamerole = $this->_dms->getClassname('role');
			$classname = $this->_dms->getClassname('user');
			foreach ($resArr as $row) {
				$role = $classnamerole::getInstance($row['role'], $this->_dms);
				$user = new $classname($row["id"], $row["login"], $row["pwd"], $row["fullName"], $row["email"], $row["language"], $row["theme"], $row["comment"], $role, $row["hidden"], $row["disabled"], $row["pwdExpiration"], $row["loginfailures"], $row["quota"], $row["homefolder"]);
				$user->setDMS($this->_dms);
				array_push($this->_rev_substitutes, $user);
			}
		}
		return $this->_rev_substitutes;
	} /* }}} */

	/**
	 * Add a substitute to the user
	 *
	 * @return boolean true if successful otherwise false
	 */
	function addSubstitute($substitute) { /* {{{ */
		$db = $this->_dms->getDB();

		if(get_class($substitute) != $this->_dms->getClassname('user'))
			return false;

		$queryStr = "SELECT * FROM `tblUserSubstitutes` WHERE `user`=" . $this->_id . " AND `substitute`=".$substitute->getID();
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) == 1) return true;

		$queryStr = "INSERT INTO `tblUserSubstitutes` (`user`, `substitute`) VALUES (" . $this->_id . ", ".$substitute->getID().")";
		if (!$db->getResult($queryStr))
			return false;

		$this->_substitutes = null;
		return true;
	} /* }}} */

	/**
	 * Remove a substitute from the user
	 *
	 * @return boolean true if successful otherwise false
	 */
	function removeSubstitute($substitute) { /* {{{ */
		$db = $this->_dms->getDB();

		if(get_class($substitute) != $this->_dms->getClassname('user'))
			return false;

		$queryStr = "SELECT * FROM `tblUserSubstitutes` WHERE `user`=" . $this->_id . " AND `substitute`=".$substitute->getID();
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) == 0) return true;

		$queryStr = "DELETE FROM `tblUserSubstitutes` WHERE `user`=" . $this->_id . " AND `substitute`=".$substitute->getID();
		if (!$db->getResult($queryStr))
			return false;

		$this->_substitutes = null;
		return true;
	} /* }}} */

	/**
	 * Check if user is a substitute of the current user
	 *
	 * @return boolean true if successful otherwise false
	 */
	function isSubstitute($substitute) { /* {{{ */
		$db = $this->_dms->getDB();

		if(get_class($substitute) != $this->_dms->getClassname('user'))
			return false;

		$queryStr = "SELECT * FROM `tblUserSubstitutes` WHERE `user`=" . $this->_id . " AND `substitute`=".$substitute->getID();
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) == 1) return true;

		return false;
	} /* }}} */

	/**
	 * Check if user may switch to the given user
	 *
	 * Switching to the given user is only allowed if the given user
	 * is a substitute for the current user.
	 *
	 * @return boolean true if successful otherwise false
	 */
	function maySwitchToUser($touser) { /* {{{ */
		$db = $this->_dms->getDB();

		if(get_class($touser) != $this->_dms->getClassname('user'))
			return false;

		/* switching to an admin account is always forbitten, unless the
		 * current user is admin itself
		 */
		if(!$this->isAdmin() && $touser->isAdmin())
			return false;

		$queryStr = "SELECT * FROM `tblUserSubstitutes` WHERE `substitute`=" . $this->_id . " AND `user`=".$touser->getID();
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) == 1) return true;

		return false;
	} /* }}} */

	/**
	 * Get all notifications of user
	 *
	 * @param integer $type type of item (T_DOCUMENT or T_FOLDER)
	 * @return SeedDMS_Core_Notification[]|bool array of notifications
	 */
	function getNotifications($type=0) { /* {{{ */
		$db = $this->_dms->getDB();
		$queryStr = "SELECT `tblNotify`.* FROM `tblNotify` ".
		 "WHERE `tblNotify`.`userID` = ". $this->_id;
		if($type) {
			$queryStr .= " AND `tblNotify`.`targetType` = ". (int) $type;
		}

		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$notifications = array();
		foreach ($resArr as $row) {
			$not = new SeedDMS_Core_Notification($row["target"], $row["targetType"], $row["userID"], $row["groupID"]);
			$not->setDMS($this);
			array_push($notifications, $not);
		}

		return $notifications;
	} /* }}} */

	/**
	 * Return list of personal keyword categories
	 *
	 * @return SeedDMS_Core_KeywordCategory[]|bool list of categories or false in case of an error
	 */
	function getKeywordCategories() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM `tblKeywordCategories` WHERE `owner` = ".$this->_id;

		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$categories = array();
		foreach ($resArr as $row) {
			$cat = new SeedDMS_Core_KeywordCategory((int) $row["id"], $row["owner"], $row["name"]);
			$cat->setDMS($this->_dms);
			array_push($categories, $cat);
		}

		return $categories;
	} /* }}} */

} /* }}} */
