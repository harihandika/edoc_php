<?php
/**
 * Implementation of the group object in the document management system
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
 * Class to represent a user group in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe, 2010 Uwe Steinmann
 * @version    Release: 6.0.15
 */
class SeedDMS_Core_RequestSoftCopy extends SeedDMS_Core_Object { /* {{{ */
	/**
	 * The id of the user group
	 *
	 * @var integer
	 */
	protected $_id;

	/**
	 * The name of the user group
	 *
	 * @var integer
	 */
	protected $_documentID;

		/**
	 * @var SeedDMS_Core_User[]
	 */
	protected $_users;
	/**
	 * The comment of the user group
	 *
	 * @var string
	 */
	protected $_keterangan;
		/**
	 * The comment of the user group
	 *
	 * @var string
	 */
	protected $_keperluan;

		/**
	 * The comment of the user group
	 *
	 * @var string
	 */
	protected $_status;
	/**
	 * @var string
	 */
	protected $_date;

		/**
	 * @var integer id of user who is the owner
	 */
	protected $_ownerID;

	/**
	 * @var boolean true if access is inherited, otherwise false
	 */
	protected $_inheritAccess;

	/**
	 * @var integer default access if access rights are not inherited
	 */
	protected $_defaultAccess;

		/**
	 * @var SeedDMS_Core_User
	 */
	protected $_owner;

	/** @var array of SeedDMS_Core_UserAccess and SeedDMS_Core_GroupAccess */
	protected $_accessList;

	/**
	 * @var array list of notifications for users and groups
	 */
	public $_notifyList;

	/**
	 * @var array temp. storage for content
	 */
	protected $_content;

	/**
	 * @var SeedDMS_Core_DocumentContent temp. storage for latestcontent
	 */
	protected $_latestContent;

	/**
	 * @var SeedDMS_Core_Folder
	 */
	protected $_parent;

	/**
	 * @var integer id of parent folder
	 */
	protected $_parentID;

		/**
	 * SeedDMS_Core_Folder constructor.
	 * @param $id
	 * @param $documentID
	 * @param $parentID
	 * @param $comment
	 * @param $date
	 * @param $ownerID
	 * @param $inheritAccess
	 * @param $defaultAccess
	 * @param $status
	 */

	function __construct($id, $documentID, $keterangan, $keperluan, $date, $ownerID, $inheritAccess, $defaultAccess, $status) { /* {{{ */
		$this->_id = $id;
		$this->_documentID = (int) $documentID;
		$this->_keterangan = $keterangan;
		$this->_keperluan = $keperluan;
		$this->_date = $date;
		$this->_ownerID = $ownerID;
		$this->_inheritAccess = $inheritAccess;
		$this->_defaultAccess = $defaultAccess;
		$this->_status = $status;
	} /* }}} */

	/**
	 * Return a folder by its database record
	 *
	 * @param array $resArr array of folder data as returned by database
	 * @param SeedDMS_Core_DMS $dms
	 * @return SeedDMS_Core_RequestSoftCopy|bool instance of SeedDMS_Core_SoftCOpy if document exists
	 */
	public static function getInstanceByData($resArr, $dms) { /* {{{ */
		$classname = $dms->getClassname('softcopy');
		/** @var SeedDMS_Core_SoftCopy $softcopy */
		$softcopy = new self($resArr["id"], $resArr["documentID"], $resArr["keterangan"], $resArr["keperluan"], $resArr["date"], $resArr["owner"], $resArr["inheritAccess"], $resArr["defaultAccess"], $resArr["status"]);
		$softcopy->setDMS($dms);
		$softcopy = $softcopy->applyDecorators();
		return $softcopy;
	} /* }}} */

	/**
	 * Return a softcopy by its id
	 *
	 * @param integer $id id of folder
	 * @param SeedDMS_Core_DMS $dms
	 * @return SeedDMS_Core_SoftCOpy|bool instance of SeedDMS_Core_SoftCOpy if document exists, null
	 * if document does not exist, false in case of error
	 */
	public static function getInstance($id, $dms) { /* {{{ */
		$db = $dms->getDB();
		$queryStr = "SELECT * FROM `tblRequestSoftCopy` WHERE `id` = " . (int) $id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		elseif (count($resArr) != 1)
			return null;

		return self::getInstanceByData($resArr[0], $dms);
	} /* }}} */


	public static function getAllInstances($orderby, $dms) { /* {{{ */
		$db = $dms->getDB();

		$queryStr = "SELECT * FROM `tblRequestSoftCopy` ORDER BY `id`";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false){
			return false;
		}

			$requestsoftcopys = array();
			for ($i = 0; $i < count($resArr); $i++) {
				$requestsoftcopy = new self($resArr[$i]["id"], $resArr[$i]["documentID"], $resArr[$i]["keterangan"],$resArr[$i]["keperluan"],$resArr[$i]["date"],$resArr[$i]["owner"],$resArr[$i]["inheritAccess"],$resArr[$i]["defaultAccess"],$resArr[$i]["status"]);
				$requestsoftcopy->setDMS($dms);
				$requestsoftcopys[$i] = $requestsoftcopy;
			}
	
			return $requestsoftcopys;
	} /* }}} */

		/**
	 * Apply decorators
	 *
	 * @return object final object after all decorators has been applied
	 */
	function applyDecorators() { /* {{{ */
		if($decorators = $this->_dms->getDecorators('document')) {
			$s = $this;
			foreach($decorators as $decorator) {
				$s = new $decorator($s);
			}
			return $s;
		} else {
			return $this;
		}
	} /* }}} */

	/**
	 * Get the documentid of the folder.
	 *
	 * @return string documentid of softcopy
	 */
	public function getDocumentID() { return $this->_documentID; }

	/**
	 * Set the name of the folder.
	 *
	 * @param string $newDocumentID set a new name of the folder
	 * @return bool
	 */
	public function setDocumentID($newDocumentID) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblRequestSoftCopy` SET `documentID` = " . $db->qstr($newDocumentID) . " WHERE `id` = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_documentID = $newDocumentID;

		return true;
	} /* }}} */

	/**
	 * @return string
	 */
	public function getKeterangan() { return $this->_keterangan; }

	/**
	 * @param $newKeterangan
	 * @return bool
	 */
	public function setKeterangan($newKeterangan) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblRequestSoftCopy` SET `keterangan` = " . $db->qstr($newKeterangan) . " WHERE `id` = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_keterangan = $newKeterangan;
		return true;
	} /* }}} */

	/**
	 * @return string
	 */
	public function getKeperluan() { return $this->_keperluan; }

	/**
	 * @param $newKeperluan
	 * @return bool
	 */
	public function setKeperluan($newKeperluan) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblRequestSoftCopy` SET `keperluan` = " . $db->qstr($newKeperluan) . " WHERE `id` = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_keperluan = $newKeperluan;
		return true;
	} /* }}} */

	/**
	 * @return string
	 */
	public function getStatus() { return $this->_status; }

	/**
	 * @param $newStatus
	 * @return bool
	 */
	public function setStatus($newStatus) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblRequestSoftCopy` SET `status` = " . $db->qstr($newStatus) . " WHERE `id` = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_status = $newStatus;
		return true;
	} /* }}} */
	


	/**
	 * Return creation date of folder
	 *
	 * @return integer unix timestamp of creation date
	 */
	public function getDate() { /* {{{ */
		return $this->_date;
	} /* }}} */

	/**
	 * Set creation date of the document
	 *
	 * @param integer $date timestamp of creation date. If false then set it
	 * to the current timestamp
	 * @return boolean true on success
	 */
	function setDate($date) { /* {{{ */
		$db = $this->_dms->getDB();

		if(!$date)
			$date = time();
		else {
			if(!is_numeric($date))
				return false;
		}

		$queryStr = "UPDATE `tblRequestSoftCopy` SET `date` = " . (int) $date . " WHERE `id` = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		$this->_date = $date;
		return true;
	} /* }}} */

	/**
	 * Return owner of document
	 *
	 * @return SeedDMS_Core_User owner of document as an instance of {@link SeedDMS_Core_User}
	 */
	public function getOwner() { /* {{{ */
		if (!isset($this->_owner))
			$this->_owner = $this->_dms->getUser($this->_ownerID);
		return $this->_owner;
	} /* }}} */

	/**
	 * Set the owner
	 *
	 * @param SeedDMS_Core_User $newOwner of the folder
	 * @return boolean true if successful otherwise false
	 */
	function setOwner($newOwner) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblRequestSoftCopy` set `owner` = " . $newOwner->getID() . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_ownerID = $newOwner->getID();
		$this->_owner = $newOwner;
		return true;
	} /* }}} */

	/**
	 * @return bool|int
	 */
	function getDefaultAccess() { /* {{{ */
		if ($this->inheritsAccess()) {
			/* Access is supposed to be inherited but it could be that there
			 * is no parent because the configured root folder id is somewhere
			 * below the actual root folder.
			 */
			$res = $this->getParent();
			if ($res)
				return $this->_parent->getDefaultAccess();
		}

		return $this->_defaultAccess;
	} /* }}} */

	/**
	 * Set default access mode
	 *
	 * This method sets the default access mode and also removes all notifiers which
	 * will not have read access anymore.
	 *
	 * @param integer $mode access mode
	 * @param boolean $noclean set to true if notifier list shall not be clean up
	 * @return bool
	 */
	function setDefaultAccess($mode, $noclean=false) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblRequestSoftCopy` set `defaultAccess` = " . (int) $mode . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_defaultAccess = $mode;

		if(!$noclean)
			$this->cleanNotifyList();

		return true;
	} /* }}} */

	function inheritsAccess() { return $this->_inheritAccess; }

	/**
	 * Set inherited access mode
	 * Setting inherited access mode will set or unset the internal flag which
	 * controls if the access mode is inherited from the parent folder or not.
	 * It will not modify the
	 * access control list for the current object. It will remove all
	 * notifications of users which do not even have read access anymore
	 * after setting or unsetting inherited access.
	 *
	 * @param boolean $inheritAccess set to true for setting and false for
	 *        unsetting inherited access mode
	 * @param boolean $noclean set to true if notifier list shall not be clean up
	 * @return boolean true if operation was successful otherwise false
	 */
	function setInheritAccess($inheritAccess, $noclean=false) { /* {{{ */
		$db = $this->_dms->getDB();

		$inheritAccess = ($inheritAccess) ? "1" : "0";

		$queryStr = "UPDATE `tblRequestSoftCopy` SET `inheritAccess` = " . (int) $inheritAccess . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_inheritAccess = $inheritAccess;

		if(!$noclean)
			$this->cleanNotifyList();

		return true;
	} /* }}} */

	

	/** @noinspection PhpUnusedParameterInspection */
	/**
	 * Get a list of all notification
	 * This function returns all users and groups that have registerd a
	 * notification for the folder
	 *
	 * @param integer $type type of notification (not yet used)
	 * @param bool $incdisabled set to true if disabled user shall be included
	 * @return SeedDMS_Core_User[]|SeedDMS_Core_Group[]|bool array with a the elements 'users' and 'groups' which
	 *        contain a list of users and groups.
	 */

	function getNotifyList($type=0, $incdisabled=false) { /* {{{ */
		if (empty($this->_notifyList)) {
			$db = $this->_dms->getDB();

			$queryStr ="SELECT * FROM `tblNotify` WHERE `targetType` = " . T_REQUESTSOFTCOPY . " AND `target` = " . $this->_id;
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_notifyList = array("groups" => array(), "users" => array());
			foreach ($resArr as $row)
			{
				if ($row["userID"] != -1) {
					$u = $this->_dms->getUser($row["userID"]);
					if($u && (!$u->isDisabled() || $incdisabled))
						array_push($this->_notifyList["users"], $u);
				} else {//if ($row["groupID"] != -1)
					$g = $this->_dms->getGroup($row["groupID"]);
					if($g)
						array_push($this->_notifyList["groups"], $g);
				}
			}
		}
		return $this->_notifyList;
	} /* }}} */

	/**
	 * Make sure only users/groups with read access are in the notify list
	 *
	 */
	function cleanNotifyList() { /* {{{ */
		// If any of the notification subscribers no longer have read access,
		// remove their subscription.
		if (empty($this->_notifyList))
			$this->getNotifyList();

		/* Make a copy of both notifier lists because removeNotify will empty
		 * $this->_notifyList and the second foreach will not work anymore.
		 */
		/** @var SeedDMS_Core_User[] $nusers */
		$nusers = $this->_notifyList["users"];
		$ngroups = $this->_notifyList["groups"];
		foreach ($nusers as $u) {
			if ($this->getAccessMode($u) < M_READ) {
				$this->removeNotify($u->getID(), true);
			}
		}

		/** @var SeedDMS_Core_Group[] $ngroups */
		foreach ($ngroups as $g) {
			if ($this->getGroupAccessMode($g) < M_READ) {
				$this->removeNotify($g->getID(), false);
			}
		}
	} /* }}} */

	/**
	 * Add a user/group to the notification list
	 * This function does not check if the currently logged in user
	 * is allowed to add a notification. This must be checked by the calling
	 * application.
	 *
	 * @param integer $userOrGroupID
	 * @param boolean $isUser true if $userOrGroupID is a user id otherwise false
	 * @return integer error code
	 *    -1: Invalid User/Group ID.
	 *    -2: Target User / Group does not have read access.
	 *    -3: User is already subscribed.
	 *    -4: Database / internal error.
	 *     0: Update successful.
	 */
	function addNotify($userOrGroupID, $isUser) { /* {{{ */
		$db = $this->_dms->getDB();

		$userOrGroup = ($isUser) ? "`userID`" : "`groupID`";

		/* Verify that user / group exists */
		/** @var SeedDMS_Core_User|SeedDMS_Core_Group $obj */
		$obj = ($isUser ? $this->_dms->getUser($userOrGroupID) : $this->_dms->getGroup($userOrGroupID));
		if (!is_object($obj)) {
			return -1;
		}

		/* Verify that the requesting user has permission to add the target to
		 * the notification system.
		 */
		/*
		 * The calling application should enforce the policy on who is allowed
		 * to add someone to the notification system. If is shall remain here
		 * the currently logged in user should be passed to this function
		 *
		GLOBAL $user;
		if ($user->isGuest()) {
			return -2;
		}
		if (!$user->isAdmin()) {
			if ($isUser) {
				if ($user->getID() != $obj->getID()) {
					return -2;
				}
			}
			else {
				if (!$obj->isMember($user)) {
					return -2;
				}
			}
		}
		*/

		//
		// Verify that user / group has read access to the document.
		//
		if ($isUser) {
			// Users are straightforward to check.
			if ($this->getAccessMode($obj) < M_READ) {
				return -2;
			}
		}
		else {
			// FIXME: Why not check the access list first and if this returns
			// not result, then use the default access?
			// Groups are a little more complex.
			if ($this->getDefaultAccess() >= M_READ) {
				// If the default access is at least READ-ONLY, then just make sure
				// that the current group has not been explicitly excluded.
				$acl = $this->getAccessList(M_NONE, O_EQ);
				$found = false;
				/** @var SeedDMS_Core_GroupAccess $group */
				foreach ($acl["groups"] as $group) {
					if ($group->getGroupID() == $userOrGroupID) {
						$found = true;
						break;
					}
				}
				if ($found) {
					return -2;
				}
			}
			else {
				// The default access is restricted. Make sure that the group has
				// been explicitly allocated access to the document.
				$acl = $this->getAccessList(M_READ, O_GTEQ);
				if (is_bool($acl)) {
					return -4;
				}
				$found = false;
				/** @var SeedDMS_Core_GroupAccess $group */
				foreach ($acl["groups"] as $group) {
					if ($group->getGroupID() == $userOrGroupID) {
						$found = true;
						break;
					}
				}
				if (!$found) {
					return -2;
				}
			}
		}
		//
		// Check to see if user/group is already on the list.
		//
		$queryStr = "SELECT * FROM `tblNotify` WHERE `tblNotify`.`target` = '".$this->_id."' ".
			"AND `tblNotify`.`targetType` = '".T_REQUESTSOFTCOPY."' ".
			"AND `tblNotify`.".$userOrGroup." = '". (int) $userOrGroupID."'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr)) {
			return -4;
		}
		if (count($resArr)>0) {
			return -3;
		}

		$queryStr = "INSERT INTO `tblNotify` (`target`, `targetType`, " . $userOrGroup . ") VALUES (" . $this->_id . ", " . T_REQUESTSOFTCOPY . ", " .  (int) $userOrGroupID . ")";
		if (!$db->getResult($queryStr))
		return -4;
	
	unset($this->_notifyList);
		return 0;
	} /* }}} */

	function getDir() { /* {{{ */
		if($this->_dms->maxDirID) {
			$dirid = (int) (($this->_id-1) / $this->_dms->maxDirID) + 1;
			return $dirid."/".$this->_id."/";
		} else {
			return $this->_id."/";
		}
	} /* }}} */

	function getLatestContent() { /* {{{ */
		if (!$this->_latestContent) {
			$db = $this->_dms->getDB();
			$queryStr = "SELECT * FROM `tblDocumentContent` WHERE `document` = ".$this->_id." ORDER BY `version` DESC";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;

			$classname = $this->_dms->getClassname('documentcontent');
			$user = $this->_dms->getLoggedInUser();
			foreach ($resArr as $row) {
				/** @var SeedDMS_Core_DocumentContent $content */
				if (!$this->_latestContent) {
					$content = new $classname($row["id"], $this, $row["version"], $row["comment"], $row["date"], $row["createdBy"], $row["dir"], $row["orgFileName"], $row["fileType"], $row["mimeType"], $row['fileSize'], $row['checksum'], $row['revisiondate']);
					if($user) {
						/* If the user may even write the document, then also allow to see all content.
						 * This is needed because the user could upload a new version
						 */
						if($content->getAccessMode($user) >= M_READ) {
							$this->_latestContent = $content;
						}
					} else {
						$this->_latestContent = $content;
					}
				}
			}
		}

		return $this->_latestContent;
	} /* }}} */

	function getDocumentFiles($version=0, $incnoversion=true) { /* {{{ */
		/* use a smarter caching because removing a document will call this function
		 * for each version and the document itself.
		 */
		$hash = substr(md5($version.$incnoversion), 0, 4);
		if (!isset($this->_documentFiles[$hash])) {
			$db = $this->_dms->getDB();

			$queryStr = "SELECT * FROM `tblDocumentFiles` WHERE `document` = " . $this->_id;
			if($version) {
				if($incnoversion)
					$queryStr .= " AND (`version`=0 OR `version`=".(int) $version.")";
				else
					$queryStr .= " AND (`version`=".(int) $version.")";
			}
			$queryStr .= " ORDER BY ";
			if($version) {
				$queryStr .= "`version` DESC,";
			}
			$queryStr .= "`date` DESC";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr) return false;

			$this->_documentFiles = array($hash=>array());

			$user = $this->_dms->getLoggedInUser();
			foreach ($resArr as $row) {
				$file = new SeedDMS_Core_DocumentFile($row["id"], $this, $row["userID"], $row["comment"], $row["date"], $row["dir"], $row["fileType"], $row["mimeType"], $row["orgFileName"], $row["name"], $row["version"], $row["public"]);
				if($file->getAccessMode($user) >= M_READ)
					array_push($this->_documentFiles[$hash], $file);
			}
		}
		return $this->_documentFiles[$hash];
	} /* }}} */

	function removeDocumentFile($ID) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!is_numeric($ID)) return false;

		$file = $this->getDocumentFile($ID);
		if (is_bool($file) && !$file) return false;

		if (file_exists( $this->_dms->contentDir . $file->getPath() )){
			if (!SeedDMS_Core_File::removeFile( $this->_dms->contentDir . $file->getPath() ))
				return false;
		}

		$name=$file->getName();
		$comment=$file->getcomment();

		$queryStr = "DELETE FROM `tblDocumentFiles` WHERE `document` = " . $this->getID() . " AND `id` = " . (int) $ID;
		if (!$db->getResult($queryStr))
			return false;

		unset ($this->_documentFiles);

		return true;
	} /* }}} */

	public function getParent() { /* {{{ */
		if ($this->_id == $this->_dms->rootFolderID || empty($this->_parentID)) {
			return false;
		}

		if (!isset($this->_parent)) {
			$this->_parent = $this->_dms->getFolder($this->_parentID);
		}
		return $this->_parent;
	} /* }}} */

	function getAccessList($mode = M_ANY, $op = O_EQ) { /* {{{ */
		$db = $this->_dms->getDB();

		if ($this->inheritsAccess()) {
			/* Access is supposed to be inherited but it could be that there
			 * is no parent because the configured root folder id is somewhere
			 * below the actual root folder.
			 */
			$res = $this->getParent();
			if ($res) {
				$pacl = $res->getAccessList($mode, $op);
				return $pacl;
			}
		} else {
			$pacl = array("groups" => array(), "users" => array());
		}

		if (!isset($this->_accessList[$mode])) {
			if ($op!=O_GTEQ && $op!=O_LTEQ && $op!=O_EQ) {
				return false;
			}
			$modeStr = "";
			if ($mode!=M_ANY) {
				$modeStr = " AND mode".$op.(int)$mode;
			}
			$queryStr = "SELECT * FROM `tblACLs` WHERE `targetType` = ".T_REQUESTSOFTCOPY.
				" AND `target` = " . $this->_id .	$modeStr . " ORDER BY `targetType`";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;

			$this->_accessList[$mode] = array("groups" => array(), "users" => array());
			foreach ($resArr as $row) {
				if ($row["userID"] != -1)
					array_push($this->_accessList[$mode]["users"], new SeedDMS_Core_UserAccess($this->_dms->getUser($row["userID"]), (int) $row["mode"]));
				else //if ($row["groupID"] != -1)
					array_push($this->_accessList[$mode]["groups"], new SeedDMS_Core_GroupAccess($this->_dms->getGroup($row["groupID"]), (int) $row["mode"]));
			}
		}

		return $this->_accessList[$mode];
		return SeedDMS_Core_DMS::mergeAccessLists($pacl, $this->_accessList[$mode]);
	} /* }}} */
	function getAccessMode($user, $context='') { /* {{{ */
		if(!$user)
			return M_NONE;

		/* Check if 'onCheckAccessFolder' callback is set */
		if(isset($this->_dms->callbacks['onCheckAccessFolder'])) {
			foreach($this->_dms->callbacks['onCheckAccessFolder'] as $callback) {
				if(($ret = call_user_func($callback[0], $callback[1], $this, $user, $context)) > 0) {
					return $ret;
				}
			}
		}

		/* Administrators have unrestricted access */
		if ($user->isAdmin()) return M_ALL;

		/* The owner of the document has unrestricted access */
		if ($user->getID() == $this->_ownerID) return M_ALL;

		/* Check ACLs */
		$accessList = $this->getAccessList();
		if (!$accessList) return false;

		/** @var SeedDMS_Core_UserAccess $userAccess */
		foreach ($accessList["users"] as $userAccess) {
			if ($userAccess->getUserID() == $user->getID()) {
				$mode = $userAccess->getMode();
				if ($user->isGuest()) {
					if ($mode >= M_READ) $mode = M_READ;
				}
				return $mode;
			}
		}

		/* Get the highest right defined by a group */
		if($accessList['groups']) {
			$mode = 0;
			/** @var SeedDMS_Core_GroupAccess $groupAccess */
			foreach ($accessList["groups"] as $groupAccess) {
				if ($user->isMemberOfGroup($groupAccess->getGroup())) {
					if ($groupAccess->getMode() > $mode)
						$mode = $groupAccess->getMode();
				}
			}
			if($mode) {
				if ($user->isGuest()) {
					if ($mode >= M_READ) $mode = M_READ;
				}
				return $mode;
			}
		}

		$mode = $this->getDefaultAccess();
		if ($user->isGuest()) {
			if ($mode >= M_READ) $mode = M_READ;
		}
		return $mode;
	} /* }}} */

// *********************************
	private function _removeContent($version) { /* {{{ */
		$db = $this->_dms->getDB();

		if (file_exists( $this->_dms->contentDir.$version->getPath() ))
			if (!SeedDMS_Core_File::removeFile( $this->_dms->contentDir.$version->getPath() ))
				return false;

		$db->startTransaction();

		$status = $version->  tus();
		$stID = $status["statusID"];

		$queryStr = "DELETE FROM `tblDocumentContent` WHERE `document` = " . $this->getID() .	" AND `version` = " . $version->getVersion();
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM `tblDocumentContentAttributes` WHERE `content` = " . $version->getId();
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM `tblTransmittalItems` WHERE `document` = '". $this->getID() ."' AND `version` = '" . $version->getVersion()."'";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM `tblDocumentStatusLog` WHERE `statusID` = '".$stID."'";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM `tblDocumentStatus` WHERE `documentID` = '". $this->getID() ."' AND `version` = '" . $version->getVersion()."'";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$status = $version->getReviewStatus();
		$stList = "";
		foreach ($status as $st) {
			$stList .= (strlen($stList)==0 ? "" : ", "). "'".$st["reviewID"]."'";
			$queryStr = "SELECT * FROM `tblDocumentReviewLog` WHERE `reviewID` = " . $st['reviewID'];
			$resArr = $db->getResultArray($queryStr);
			if ((is_bool($resArr) && !$resArr)) {
				$db->rollbackTransaction();
				return false;
			}
			foreach($resArr as $res) {
				$file = $this->_dms->contentDir . $this->getDir().'r'.$res['reviewLogID'];
				if(file_exists($file))
					SeedDMS_Core_File::removeFile($file);
			}
		}

		if (strlen($stList)>0) {
			$queryStr = "DELETE FROM `tblDocumentReviewLog` WHERE `tblDocumentReviewLog`.`reviewID` IN (".$stList.")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
		}
		$queryStr = "DELETE FROM `tblDocumentReviewers` WHERE `documentID` = '". $this->getID() ."' AND `version` = '" . $version->getVersion()."'";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$status = $version->getApprovalStatus();
		$stList = "";
		foreach ($status as $st) {
			$stList .= (strlen($stList)==0 ? "" : ", "). "'".$st["approveID"]."'";
			$queryStr = "SELECT * FROM `tblDocumentApproveLog` WHERE `approveID` = " . $st['approveID'];
			$resArr = $db->getResultArray($queryStr);
			if ((is_bool($resArr) && !$resArr)) {
				$db->rollbackTransaction();
				return false;
			}
			foreach($resArr as $res) {
				$file = $this->_dms->contentDir . $this->getDir().'a'.$res['approveLogID'];
				if(file_exists($file))
					SeedDMS_Core_File::removeFile($file);
			}
		}

		if (strlen($stList)>0) {
			$queryStr = "DELETE FROM `tblDocumentApproveLog` WHERE `tblDocumentApproveLog`.`approveID` IN (".$stList.")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
		}
		$queryStr = "DELETE FROM `tblDocumentApprovers` WHERE `documentID` = '". $this->getID() ."' AND `version` = '" . $version->getVersion()."'";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		/* Remove all receipts of document version.
		 * This implmentation is different from the above for removing approvals
		 * and reviews. It doesn't use getReceiptStatus() but reads the database
		 */
		$queryStr = "SELECT * FROM `tblDocumentRecipients` WHERE `documentID` = '". $this->getID() ."' AND `version` = '" . $version->getVersion()."'";
		$resArr = $db->getResultArray($queryStr);
		if ((is_bool($resArr) && !$resArr)) {
			$db->rollbackTransaction();
			return false;
		}

		$stList = array();
		foreach($resArr as $res) {
			$stList[] = $res['receiptID'];
		}

		if ($stList) {
			$queryStr = "DELETE FROM `tblDocumentReceiptLog` WHERE `receiptID` IN (".implode(',', $stList).")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
			$queryStr = "DELETE FROM `tblDocumentRecipients` WHERE `receiptID` IN (".implode(',', $stList).")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
		}

		/* Remove all revisions of document version.
		 * This implementation is different from the above for removing approvals
		 * and reviews. It doesn't use getRevisionStatus() but reads the database
		 */
		$queryStr = "SELECT * FROM `tblDocumentRevisors` WHERE `documentID` = '". $this->getID() ."' AND `version` = '" . $version->getVersion()."'";
		$resArr = $db->getResultArray($queryStr);
		if ((is_bool($resArr) && !$resArr)) {
			$db->rollbackTransaction();
			return false;
		}

		$stList = array();
		foreach($resArr as $res) {
			$stList[] = $res['revisionID'];
		}

		if ($stList) {
			$queryStr = "DELETE FROM `tblDocumentRevisionLog` WHERE `revisionID` IN (".implode(',', $stList).")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
			$queryStr = "DELETE FROM `tblDocumentRevisors` WHERE `revisionID` IN (".implode(',', $stList).")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
		}

		$queryStr = "DELETE FROM `tblWorkflowDocumentContent` WHERE `document` = '". $this->getID() ."' AND `version` = '" . $version->getVersion()."'";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		/* Will be deleted automatically when record will be deleted
		 * from tblWorkflowDocumentContent
		$queryStr = "DELETE FROM `tblWorkflowLog` WHERE `document` = '". $this->getID() ."' AND `version` = '" . $version->getVersion."'";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		 */

		// remove only those document files attached to version
		$res = $this->getDocumentFiles($version->getVersion(), false);
		if (is_bool($res) && !$res) {
			$db->rollbackTransaction();
			return false;
		}

		foreach ($res as $documentfile)
			if(!$this->removeDocumentFile($documentfile->getId())) {
				$db->rollbackTransaction();
				return false;
			}

		$db->commitTransaction();
		return true;
	} /* }}} */
	// rejectNotify
	// approveNotify

	function receiveNotify() { /* {{{ */
		$db = $this->_dms->getDB();

		/* Verify that user / group exists. */
		/** @var SeedDMS_Core_Group|SeedDMS_Core_User $obj */

		$queryStr = "UPDATE `tblRequestSoftCopy` SET `status` = ". 1 ." WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		return true;
	}
	 /* }}} */
	function declineNotify() { /* {{{ */
		$db = $this->_dms->getDB();

		/* Verify that user / group exists. */
		/** @var SeedDMS_Core_Group|SeedDMS_Core_User $obj */

		$queryStr = "UPDATE `tblRequestSoftCopy` SET `status` = ". -1 ." WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		return true;
	} /* }}} */

	function approveNotify() { /* {{{ */
		$db = $this->_dms->getDB();

		/* Verify that user / group exists. */
		/** @var SeedDMS_Core_Group|SeedDMS_Core_User $obj */

		$queryStr = "UPDATE `tblRequestSoftCopy` SET `status` = ". 2 ." WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		return true;
	} /* }}} */

	function rejectNotify() { /* {{{ */
		$db = $this->_dms->getDB();

		/* Verify that user / group exists. */
		/** @var SeedDMS_Core_Group|SeedDMS_Core_User $obj */

		$queryStr = "UPDATE `tblRequestSoftCopy` SET `status` = ". -2 ." WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		return true;
	} /* }}} */

	function removeNotify() { /* {{{ */
		$db = $this->_dms->getDB();

		/* Verify that user / group exists. */
		/** @var SeedDMS_Core_Group|SeedDMS_Core_User $obj */

		$queryStr = "DELETE FROM `tblRequestSoftCopy` WHERE `id` = " . $this->_id;

		if (!$db->getResult($queryStr))
			return false;
		return true;
	} /* }}} */

}
	