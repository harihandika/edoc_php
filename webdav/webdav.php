<?php

require_once "HTTP/WebDAV/Server.php";

/**
 * SeedDMS access using WebDAV
 *
 * @access  public
 * @author  Uwe Steinmann <steinm@php.net>
 * @version @package-version@
 */
class HTTP_WebDAV_Server_SeedDMS extends HTTP_WebDAV_Server
{
	/**
	 * A reference of the DMS itself
	 *
	 * This is set by ServeRequest
	 *
	 * @access private
	 * @var	object
	 */
	var $dms = null;

	/**
	 * A reference to a logger
	 *
	 * This is set by ServeRequest
	 *
	 * @access private
	 * @var	object
	 */
	var $logger = null;

	/**
	 * A reference to a notifier
	 *
	 * This is set by ServeRequest
	 *
	 * @access private
	 * @var	object
	 */
	var $notifier = null;

	/**
	 * Currently logged in user
	 *
	 * @access private
	 * @var	string
	 */
	var $user = "";

	/**
	 * Set to true if original file shall be used instead of document name
	 * This can lead to duplicate file names in a directory because the original
	 * file name is not unique. You can enforce uniqueness by setting $prefixorgfilename
	 * to true which will add the document id and version in front of the original
	 * filename.
	 *
	 * @access private
	 * @var	boolean
	 */
	var $useorgfilename = false;

	/**
	 * Set to true if original file is used and you want to prefix each filename
	 * by its document id and version, e.g. 12345-1-somefile.pdf
	 * This is option is only used fi $useorgfilename is set to true.
	 *
	 * @access private
	 * @var	boolean
	 */
	var $prefixorgfilename = true;

	/**
	 * Serve a webdav request
	 *
	 * @access public
	 * @param  object $dms reference to DMS
	 */
	function ServeRequest($dms = null, $logger = null, $notifier = null) /* {{{ */
	{
		// set root directory, defaults to webserver document root if not set
		if ($dms) {
			$this->dms = $dms;
		} else {
			return false;
		}

		// set logger
		$this->logger = $logger;

		// set notifier
		$this->notifier = $notifier;

		// special treatment for litmus compliance test
		// reply on its identifier header
		// not needed for the test itself but eases debugging
		if( function_exists('apache_request_headers') ) {
			foreach (apache_request_headers() as $key => $value) {
				if (stristr($key, "litmus")) {
					if($this->logger)
						$this->logger->log('Litmus test '.$value, PEAR_LOG_DEBUG);
					header("X-Litmus-reply: ".$value);
				}
			}
		}

		// let the base class do all the work
		parent::ServeRequest();
	} /* }}} */

	/**
	 * Log array of options as passed to most functions
	 *
	 * @access private
	 * @param  string webdav methode that was called
	 * @param  array  options
	 */
	function log_options($methode, $options) { /* {{{ */
		if($this->logger) {
			switch($methode) {
			case 'MOVE':
			case 'COPY':
				$msg = $methode.': '.$options['path'].' -> '.$options['dest'];
				break;
			default:
				$msg = $methode.': '.$options['path'];
			}
			$this->logger->log($msg, PEAR_LOG_INFO);
			foreach($options as $key=>$option) {
				if(is_array($option)) {
					$this->logger->log($methode.': '.$key.'='.var_export($option, true), PEAR_LOG_DEBUG);
				} else {
					$this->logger->log($methode.': '.$key.'='.$option, PEAR_LOG_DEBUG);
				}
			}
		}
	} /* }}} */

	/**
	 * No authentication is needed here
	 *
	 * @access private
	 * @param  string  HTTP Authentication type (Basic, Digest, ...)
	 * @param  string  Username
	 * @param  string  Password
	 * @return bool	true on successful authentication
	 */
	function check_auth($type, $user, $pass) /* {{{ */
	{
		global $settings;

		if($this->logger)
			$this->logger->log('check_auth: type='.$type.', user='.$user.'', PEAR_LOG_INFO);

		$userobj = false;

		/* Authenticate against LDAP server {{{ */
		if (!$userobj && isset($settings->_ldapHost) && strlen($settings->_ldapHost)>0) {
			require_once("../inc/inc.ClassLdapAuthentication.php");
			$authobj = new SeedDMS_LdapAuthentication($this->dms, $settings);
			$userobj = $authobj->authenticate($user, $pass);
		} /* }}} */

		/* Authenticate against SeedDMS database {{{ */
		if(!$userobj) {
			require_once("../inc/inc.ClassDbAuthentication.php");
			$authobj = new SeedDMS_DbAuthentication($this->dms, $settings);
			$userobj = $authobj->authenticate($user, $pass);
		} /* }}} */

		if(!$userobj) {
			if($this->logger)
				$this->logger->log('check_auth: No such user'.$user, PEAR_LOG_NOTICE);
			return false;
		}

		if(($userobj->getID() == $settings->_guestID) && (!$settings->_enableGuestLogin)) {
			if($this->logger)
				$this->logger->log('check_auth: Login as guest is not allowed', PEAR_LOG_NOTICE);
			return false;
		}

		if($userobj->isDisabled())
			return false;

		if($userobj->isAdmin() && ($_SERVER['REMOTE_ADDR'] != $settings->_adminIP ) && ( $settings->_adminIP != ""))
			return false;

		$this->user = $userobj;

		return true;
	} /* }}} */


	/**
	 * Get the object id from its path
	 *
	 * @access private
	 * @param  string  path
	 * @return bool/object object with given path or false on error
	 */
	function reverseLookup($path) /* {{{ */
	{
		$path = rawurldecode($path);
		if($this->logger)
			$this->logger->log('reverseLookup: path='.$path.'', PEAR_LOG_DEBUG);

		$root = $this->dms->getRootFolder();
		if($path[0] == '/') {
			$path = substr($path, 1);
		}
		$patharr = explode('/', $path);
		/* The last entry is always the document, though if the path ends
		 * in '/', the document name will be empty.
		 */
		$docname = array_pop($patharr);
		$parentfolder = $root;

		if(!$patharr) {
			if(!$docname) {
				if($this->logger)
					$this->logger->log('reverseLookup: found folder '.$root->getName().' ('.$root->getID().')', PEAR_LOG_DEBUG);
				return $root;
			} else {
				if($this->useorgfilename) {
					if($this->prefixorgfilename) {
						$tmp = explode('-', $docname, 3);
						if(ctype_digit($tmp[0])) {
							$document = $this->dms->getDocument((int) $tmp[0]);
						} else {
							$document = null;
						}
					} else {
						$document = $this->dms->getDocumentByOriginalFilename($docname, $root);
					}
				} else
					$document = $this->dms->getDocumentByName($docname, $root);
				if($document) {
					if($this->logger)
						$this->logger->log('reverseLookup: found document '.$document->getName().' ('.$document->getID().')', PEAR_LOG_DEBUG);
					return $document;
				} else {
					return false;
				}
			}
		}

		foreach($patharr as $pathseg) {
			if($folder = $this->dms->getFolderByName($pathseg, $parentfolder)) {
				$parentfolder = $folder;
			}
		}
		if($folder) {
			if($docname) {
				if($this->useorgfilename) {
					if($this->prefixorgfilename) {
						$tmp = explode('-', $docname, 3);
						if(ctype_digit($tmp[0])) {
							$document = $this->dms->getDocument((int) $tmp[0]);
						} else {
							$document = null;
						}
					} else {
						$document = $this->dms->getDocumentByOriginalFilename($docname, $folder);
					}
				} else
					$document = $this->dms->getDocumentByName($docname, $folder);
				if($document) {
					if($this->logger)
						$this->logger->log('reverseLookup: found document '.$document->getName().' ('.$document->getID().')', PEAR_LOG_DEBUG);
					return $document;
				} else {
					if($this->logger)
						$this->logger->log('reverseLookup: nothing found', PEAR_LOG_DEBUG);
					return false;
				}
			} else {
				if($this->logger)
					$this->logger->log('reverseLookup: found folder '.$folder->getName().' ('.$folder->getID().')', PEAR_LOG_DEBUG);
				return $folder;
			}
		} else {
			if($this->logger)
				$this->logger->log('reverseLookup: nothing found', PEAR_LOG_DEBUG);
			return false;
		}
		if($this->logger)
			$this->logger->log('reverseLookup: nothing found', PEAR_LOG_DEBUG);
		return false;
	} /* }}} */


	/**
	 * PROPFIND method handler
	 *
	 * @param  array  general parameter passing array
	 * @param  array  return array for file properties
	 * @return bool   true on success
	 */
	function PROPFIND(&$options, &$files) /* {{{ */
	{
		$this->log_options('PROFIND', $options);

		// get folder or document from path
		$obj = $this->reverseLookup($options["path"]);

		// sanity check
		if (!$obj) {
			$obj = $this->reverseLookup($options["path"].'/');
			if(!$obj)
				return false;
		}

		// prepare property array
		$files["files"] = array();

		// store information for the requested path itself
		$files["files"][] = $this->fileinfo($obj);

		// information for contained resources requested?
		if (get_class($obj) == $this->dms->getClassname('folder') && !empty($options["depth"])) {

			$subfolders = $obj->getSubFolders();
			$subfolders = SeedDMS_Core_DMS::filterAccess($subfolders, $this->user, M_READ);
			if ($subfolders) {
				// ok, now get all its contents
				foreach($subfolders as $subfolder) {
					$files["files"][] = $this->fileinfo($subfolder);
				}
				// TODO recursion needed if "Depth: infinite"
			}
			$documents = $obj->getDocuments();
			$docs = SeedDMS_Core_DMS::filterAccess($documents, $this->user, M_READ);
			if(!$this->user->isAdmin()) {
				$documents = array();
				foreach($docs as $document) {
					$lc = $document->getLatestContent();
					$status = $lc->getStatus();
					if($status['status'] == S_RELEASED) {
						$documents[] = $document;
					}
				}
			} else {
				$documents = $docs;
			}
			if ($documents) {
				// ok, now get all its contents
				foreach($documents as $document) {
					$files["files"][] = $this->fileinfo($document);
				}
			}
		}

		// ok, all done
		return true;
	} /* }}} */

	/**
	 * Get properties for a single file/resource
	 *
	 * @param  string  resource path
	 * @return array   resource properties
	 */
	function fileinfo($obj) /* {{{ */
	{
		// create result array
		$info = array();
		$info["props"] = array();

		// type and size (caller already made sure that path exists)
		if (get_class($obj) == $this->dms->getClassname('folder')) {
			// modification time
			/* folders do not have a modification time */
			$info["props"][] = $this->mkprop("getlastmodified", $obj->getDate());
			$info["props"][] = $this->mkprop("creationdate", $obj->getDate());

			// directory (WebDAV collection)
			$patharr = $obj->getPath();
			array_shift($patharr);
			$path = '';
			foreach($patharr as $pathseg)
//				$path .= '/'.rawurlencode($pathseg->getName());
				$path .= '/'.$pathseg->getName();
			if(!$path) {
				$path = '/';
				$info["props"][] = $this->mkprop("isroot", "true");
			}
//			$info["path"] = htmlspecialchars($path);
			$info["path"] = $path;
			$info["props"][] = $this->mkprop("displayname", $obj->getName());
			$info["props"][] = $this->mkprop("resourcetype", "collection");
			$info["props"][] = $this->mkprop("getcontenttype", "httpd/unix-directory");
		} else {
			// modification time
			$info["props"][] = $this->mkprop("getlastmodified",$obj->getLatestContent()->getDate());
			$info["props"][] = $this->mkprop("creationdate",	$obj->getDate());

			// plain file (WebDAV resource)
			$content = $obj->getLatestContent();
			$fspath = $content->getPath();
			$patharr = $obj->getFolder()->getPath();
			array_shift($patharr);
			$path = '/';
			foreach($patharr as $pathseg)
//				$path .= rawurlencode($pathseg->getName()).'/';
				$path .= $pathseg->getName().'/';
//			$info["path"] = htmlspecialchars($path.rawurlencode($obj->getName()));
			if($this->useorgfilename) {
				/* Add the document id and version to the display name.
				 * I doesn't harm because for
				 * accessing the document the full path is used by the browser
				 */
				if($this->prefixorgfilename) {
					$info["path"] = $path.$obj->getID()."-".$content->getVersion()."-".$content->getOriginalFileName();
					$info["props"][] = $obj->getID()."-".$content->getVersion()."-".$content->getOriginalFileName();
				} else {
					$info["path"] = $path.$content->getOriginalFileName();
					$info["props"][] = $this->mkprop("displayname", $content->getOriginalFileName());
				}
			} else {
				$info["path"] = $path.$obj->getName();
				$info["props"][] = $this->mkprop("displayname", $obj->getName());
			}

			$info["props"][] = $this->mkprop("resourcetype", "");
			if (1 /*is_readable($fspath)*/) {
				$info["props"][] = $this->mkprop("getcontenttype", $content->getMimeType());
			} else {
				$info["props"][] = $this->mkprop("getcontenttype", "application/x-non-readable");
			}
			if(file_exists($this->dms->contentDir.'/'.$fspath))
				$info["props"][] = $this->mkprop("getcontentlength", filesize($this->dms->contentDir.'/'.$fspath));
			else
				$info["props"][] = $this->mkprop("getcontentlength", 0);
			if($keywords = $obj->getKeywords())
				$info["props"][] = $this->mkprop("SeedDMS:", "keywords", $keywords);
			$info["props"][] = $this->mkprop("SeedDMS:", "id", $obj->getID());
			$info["props"][] = $this->mkprop("SeedDMS:", "version", $content->getVersion());
			$status = $content->getStatus();
			$info["props"][] = $this->mkprop("SeedDMS:", "status", $status['status']);
			$info["props"][] = $this->mkprop("SeedDMS:", "status-comment", $status['comment']);
			$info["props"][] = $this->mkprop("SeedDMS:", "status-date", $status['date']);
			if($obj->getExpires())
				$info["props"][] = $this->mkprop("SeedDMS:", "expires", date('c', $obj->getExpires()));
		}
		if($comment = $obj->getComment())
			$info["props"][] = $this->mkprop("SeedDMS:", "comment", $comment);
		$info["props"][] = $this->mkprop("SeedDMS:", "owner", $obj->getOwner()->getLogin());

		$attributes = $obj->getAttributes();
		if($attributes) {
			foreach($attributes as $attribute) {
				$attrdef = $attribute->getAttributeDefinition();
				$valueset = $attrdef->getValueSetAsArray();
				if($valueset && $attrdef->getMultipleValues()) {
					$valuesetstr = $attrdef->getValueSet();
					$delimiter = substr($valuesetstr, 0, 1);
					$info["props"][] = $this->mkprop("SeedDMS:", 'attr_'.str_replace(array(' ', '|'), array('', ''), $attrdef->getName()), $delimiter.implode($delimiter, $attribute->getValueAsArray()));
				} else
					$info["props"][] = $this->mkprop("SeedDMS:", 'attr_'.str_replace(array(' ','|'), array('', '',''), $attrdef->getName()), $attribute->getValue());
			}
		}

		return $info;
	} /* }}} */

	/**
	 * GET method handler
	 * 
	 * @param  array  parameter passing array
	 * @return bool   true on success
	 */
	function GET(&$options) /* {{{ */
	{
		$this->log_options('GET', $options);

		// get folder or document from path
		$obj = $this->reverseLookup($options["path"]);

		// sanity check
		if (!$obj) return false;

		// is this a collection?
		if (get_class($obj) == $this->dms->getClassname('folder')) {
			return $this->GetDir($obj, $options);
		}

		$content = $obj->getLatestContent();

		// detect resource type
		$options['mimetype'] = $content->getMimeType(); 

		// detect modification time
		// see rfc2518, section 13.7
		// some clients seem to treat this as a reverse rule
		// requiering a Last-Modified header if the getlastmodified header was set
		$options['mtime'] = $content->getDate();

		$fspath = $this->dms->contentDir.'/'.$content->getPath();
		if(!file_exists($fspath))
			return false;
		// detect resource size
		$options['size'] = filesize($fspath);

		// no need to check result here, it is handled by the base class
		$options['stream'] = fopen($fspath, "r");

		return true;
	} /* }}} */

	/**
	 * GET method handler for directories
	 *
	 * This is a very simple mod_index lookalike.
	 * See RFC 2518, Section 8.4 on GET/HEAD for collections
	 *
	 * @param  object  folder object
	 * @return void	function has to handle HTTP response itself
	 */
	function GetDir($folder, &$options) /* {{{ */
	{
		// fixed width directory column format
		$format = "%15s  %-19s  %-s\n";

		$subfolders = $folder->getSubFolders();
		$subfolders = SeedDMS_Core_DMS::filterAccess($subfolders, $this->user, M_READ);
		$documents = $folder->getDocuments();
		$docs = SeedDMS_Core_DMS::filterAccess($documents, $this->user, M_READ);
		if(!$this->user->isAdmin()) {
			$documents = array();
			foreach($docs as $document) {
				$lc = $document->getLatestContent();
				$status = $lc->getStatus();
				if($status['status'] == S_RELEASED) {
					$documents[] = $document;
				}
			}
		} else {
			$documents = $docs;
		}

		$objs = array_merge($subfolders, $documents);

		echo "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><title>Index of ".htmlspecialchars($options['path'])."</title></head>\n";

		echo "<h1>Index of ".htmlspecialchars($options['path'])."</h1>\n";

		echo "<pre>";
		printf($format, "Size", "Last modified", "Filename");
		echo "<hr>";

		$parents = $folder->getPath();
		$_fullpath = '/';
		if(count($parents) > 1) {
			$p = array_slice($parents, -2, 1);
			$p = $p[0];
			array_shift($parents);
			$last = array_pop($parents);
			foreach($parents as $parent)
				$_fullpath .= $parent->getName().'/';
			printf($format, 0, strftime("%Y-%m-%d %H:%M:%S", $p->getDate()), "<a href=\"".$_SERVER['SCRIPT_NAME'].htmlspecialchars($_fullpath)."\">..</a>");
			$_fullpath .= $last->getName().'/';
		}
		foreach ($objs as $obj) {
			if(get_class($obj) == $this->dms->getClassname('folder')) {
				$fullpath = $_fullpath.$obj->getName().'/';
				$displayname = $obj->getName().'/';
				$filesize = 0;
				$mtime = $obj->getDate();
			} else {
				$content = $obj->getLatestContent();

				$mimetype = $content->getMimeType(); 

				$mtime = $content->getDate();

				$fspath = $this->dms->contentDir.'/'.$content->getPath();
				if(file_exists($fspath))
					$filesize = filesize($fspath);
				else
					$filesize = 0;
				if($this->useorgfilename) {
					/* Add the document id and version to the display name.
					 * I doesn't harm because for
					 * accessing the document the full path is used by the browser
					 */
					if($this->prefixorgfilename) {
						$displayname = $obj->getID()."-".$content->getVersion()."-".$content->getOriginalFileName();
						$fullpath = $_fullpath.$obj->getID()."-".$content->getVersion()."-".$content->getOriginalFileName();
					} else {
						$displayname = $content->getOriginalFileName();
						$fullpath = $_fullpath.$content->getOriginalFileName();
					}
				} else {
					$displayname = $obj->getName();
					$fullpath = $_fullpath.$displayname;
				}
			}
			printf($format, 
				   number_format($filesize),
				   strftime("%Y-%m-%d %H:%M:%S", $mtime), 
				   "<a href=\"".$_SERVER['SCRIPT_NAME'].htmlspecialchars($fullpath)."\">".htmlspecialchars($displayname, ENT_QUOTES)."</a>");
		}

		echo "</pre>";

		echo "</html>\n";

		exit;
	} /* }}} */

	/**
	 * PUT method handler
	 * 
	 * @param  array  parameter passing array
	 * @return bool   true on success
	 */
	function PUT(&$options) /* {{{ */
	{
		global $settings, $fulltextservice;

		$this->log_options('PUT', $options);

		$path   = $options["path"];
		$parent = dirname($path);
		$name   = basename($path);

		// get folder from path
		if($parent == '/')
			$parent = '';
		$folder = $this->reverseLookup($parent.'/');

		if (!$folder || get_class($folder) != $this->dms->getClassname('folder')) {
			return "409 Conflict";
		}

		/* Check if user is logged in */
		if(!$this->user) {
			if($this->logger)
				$this->logger->log('PUT: access forbidden', PEAR_LOG_ERR);
			return "403 Forbidden";				 
		}

		$tmpFile = tempnam('/tmp', 'webdav');
		$fp = fopen($tmpFile, 'w');
		while(!feof($options["stream"])) {
			$data = fread($options["stream"], 1000);
			fwrite($fp, $data);
		}
		fclose($fp);

		$finfo = new finfo(FILEINFO_MIME);
		$mimetype = $finfo->file($tmpFile);

		$tmp = explode(';', $mimetype);
		$mimetype = $tmp[0];
		switch($mimetype) {
			case 'application/pdf';
				$fileType = ".pdf";
				break;
			default:
				$lastDotIndex = strrpos($name, ".");
				if($lastDotIndex === false) $fileType = ".";
				else $fileType = substr($name, $lastDotIndex);
		}
		if($this->logger)
			$this->logger->log('PUT: file is of type '.$mimetype, PEAR_LOG_INFO);

		/* First check whether there is already a file with the same name */
		if($this->useorgfilename) {
			if($this->prefixorgfilename) {
				$tmp = explode('-', $name, 3);
				if(ctype_digit($tmp[0])) {
					$document = $this->dms->getDocument((int) $tmp[0]);
				} else {
					$document = null;
				}
			} else {
				$document = $this->dms->getDocumentByOriginalFilename($name, $folder);
			}
		} else
			$document = $this->dms->getDocumentByName($name, $folder);
		if($document) {
			if($this->logger)
				$this->logger->log('PUT: saving document id='.$document->getID(), PEAR_LOG_INFO);
			if ($document->getAccessMode($this->user, 'updateDocument') < M_READWRITE) {
				if($this->logger)
					$this->logger->log('PUT: no access on document', PEAR_LOG_ERR);
				unlink($tmpFile);
				return "403 Forbidden";
			} else {
				/* Check if the new version iѕ identical to the current version.
				 * In that case just update the modification date
				 */
				$lc = $document->getLatestContent();
				if($lc->getChecksum() == SeedDMS_Core_File::checksum($tmpFile)) {
					if($this->logger)
						$this->logger->log('PUT: identical to latest version', PEAR_LOG_INFO);
					$lc->setDate();
				} else {
					if($this->user->getID() == $lc->getUser()->getID() &&
						 $name == $lc->getOriginalFileName() &&
						 $fileType == $lc->getFileType() &&
						 $mimetype == $lc->getMimeType() &&
						 $settings->_enableWebdavReplaceDoc) {
						if($this->logger)
							$this->logger->log('PUT: replacing latest version', PEAR_LOG_INFO);
						if(!$document->replaceContent($lc->getVersion(), $this->user, $tmpFile, $name, $fileType, $mimetype)) {
							if($this->logger)
								$this->logger->log('PUT: error replacing latest version', PEAR_LOG_ERR);
							unlink($tmpFile);
							return "403 Forbidden";
						}
						/* set $content for notification */
						$content = $lc;
					} else {
						if($this->logger)
							$this->logger->log('PUT: adding new version', PEAR_LOG_INFO);

						$reviewers = array('i'=>[], 'g'=>[]);
						$approvers = array('i'=>[], 'g'=>[]);
						$workflow = null;
						if($settings->_workflowMode == 'traditional' || $settings->_workflowMode == 'traditional_only_approval') {
							if($settings->_workflowMode == 'traditional') {
								$reviewers = getMandatoryReviewers($document->getFolder(), $this->user);
							}
							$approvers = getMandatoryApprovers($document->getFolder(), $this->user);
						} elseif($settings->_workflowMode == 'advanced') {
							if($workflows = $user->getMandatoryWorkflows()) {
								$workflow = array_shift($workflows);
							}
						}

						$controller = Controller::factory('UpdateDocument');
						$controller->setParam('dms', $this->dms);
						$controller->setParam('user', $this->user);
						$controller->setParam('documentsource', 'webdav');
						$controller->setParam('folder', $document->getFolder());
						$controller->setParam('document', $document);
						$controller->setParam('fulltextservice', $fulltextservice);
						$controller->setParam('comment', '');
						$controller->setParam('userfiletmp', $tmpFile);
						$controller->setParam('userfilename', $name);
						$controller->setParam('filetype', $fileType);
						$controller->setParam('userfiletype', $mimetype);
						$controller->setParam('reviewers', $reviewers);
						$controller->setParam('approvers', $approvers);
						$controller->setParam('attributes', array());
						$controller->setParam('workflow', $workflow);

						if(!$content = $controller->run()) {
							if($this->logger)
								$this->logger->log('PUT: error adding new version', PEAR_LOG_ERR);
							unlink($tmpFile);
							return "409 Conflict";
						}
					}
					if($this->notifier) {
						if($this->logger)
							$this->logger->log('PUT: Sending Notifications', PEAR_LOG_INFO);
						$notifyList = $document->getNotifyList();
						$folder = $document->getFolder();

						$subject = "document_updated_email_subject";
						$message = "document_updated_email_body";
						$params = array();
						$params['name'] = $document->getName();
						$params['folder_path'] = $folder->getFolderPathPlain();
						$params['username'] = $this->user->getFullName();
						$params['comment'] = $document->getComment();
						$params['version_comment'] = $content->getComment();
						$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
						$params['sitename'] = $settings->_siteName;
						$params['http_root'] = $settings->_httpRoot;
						$this->notifier->toList($this->user, $notifyList["users"], $subject, $message, $params);
						foreach ($notifyList["groups"] as $grp) {
							$this->notifier->toGroup($this->user, $grp, $subject, $message, $params);
						}
					}
				}
			}
		} else {
			if($this->logger)
				$this->logger->log('PUT: adding new document', PEAR_LOG_INFO);
			if ($folder->getAccessMode($this->user, 'addDocument') < M_READWRITE) {
				if($this->logger)
					$this->logger->log('PUT: no access on folder', PEAR_LOG_ERR);
				unlink($tmpFile);
				return "403 Forbidden";
			} 

			/* Check if name already exists in the folder */
			/*
			if(!$settings->_enableDuplicateDocNames) {
				if($folder->hasDocumentByName($name)) {
					return "403 Forbidden";				 
				}
			}
			 */

			$reviewers = array('i'=>[], 'g'=>[]);
			$approvers = array('i'=>[], 'g'=>[]);
			$workflow = null;
			if($settings->_workflowMode == 'traditional' || $settings->_workflowMode == 'traditional_only_approval') {
				if($settings->_workflowMode == 'traditional') {
					$reviewers = getMandatoryReviewers($folder, $this->user);
				}
				$approvers = getMandatoryApprovers($folder, $this->user);
			} elseif($settings->_workflowMode == 'advanced') {
				if($workflows = $user->getMandatoryWorkflows()) {
					$workflow = array_shift($workflows);
				}
			}

			$controller = Controller::factory('AddDocument');
			$controller->setParam('dms', $this->dms);
			$controller->setParam('user', $this->user);
			$controller->setParam('documentsource', 'webdav');
			$controller->setParam('folder', $folder);
			$controller->setParam('fulltextservice', $fulltextservice);
			$controller->setParam('name', $name);
			$controller->setParam('comment', '');
			$controller->setParam('expires', 0);
			$controller->setParam('keywords', '');
			$controller->setParam('categories', array());
			$controller->setParam('owner', $this->user);
			$controller->setParam('userfiletmp', $tmpFile);
			$controller->setParam('userfilename', $name);
			$controller->setParam('filetype', $fileType);
			$controller->setParam('userfiletype', $mimetype);
			$minmax = $folder->getDocumentsMinMax();
			if($settings->_defaultDocPosition == 'start')
				$controller->setParam('sequence', $minmax['min'] - 1);
			else
				$controller->setParam('sequence', $minmax['max'] + 1);
			$controller->setParam('reviewers', $reviewers);
			$controller->setParam('approvers', $approvers);
			$controller->setParam('reqversion', 0);
			$controller->setParam('versioncomment', '');
			$controller->setParam('attributes', array());
			$controller->setParam('attributesversion', array());
			$controller->setParam('workflow', $workflow);
			$controller->setParam('notificationgroups', array());
			$controller->setParam('notificationusers', array());
			$controller->setParam('maxsizeforfulltext', $settings->_maxSizeForFullText);
			$controller->setParam('defaultaccessdocs', $settings->_defaultAccessDocs);
			if(!$document = $controller->run()) {
//			if(!$res = $folder->addDocument($name, '', 0, $this->user, '', array(), $tmpFile, $name, $fileType, $mimetype, 0, array(), array(), 0, "")) {
				unlink($tmpFile);
				if($this->logger)
					$this->logger->log('PUT: error adding object: '.$controller->getErrorMsg(), PEAR_LOG_ERR);
				return "409 Conflict ".$controller->getErrorMsg();
			}
			if($this->notifier) {
				if($this->logger)
					$this->logger->log('PUT: Sending Notifications', PEAR_LOG_INFO);
				$fnl = $folder->getNotifyList();
				$dnl = $document->getNotifyList();
				$nl = array(
					'users'=>array_unique(array_merge($dnl['users'], $fnl['users']), SORT_REGULAR),
					'groups'=>array_unique(array_merge($dnl['groups'], $fnl['groups']), SORT_REGULAR)
				);

				$subject = "new_document_email_subject";
				$message = "new_document_email_body";
				$params = array();
				$params['name'] = $name;
				$params['folder_name'] = $folder->getName();
				$params['folder_path'] = $folder->getFolderPathPlain();
				$params['username'] = $this->user->getFullName();
				$params['comment'] = '';
				$params['version_comment'] = '';
				$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
				$params['sitename'] = $settings->_siteName;
				$params['http_root'] = $settings->_httpRoot;
				$this->notifier->toList($this->user, $nl["users"], $subject, $message, $params);
				foreach ($nl["groups"] as $grp) {
					$this->notifier->toGroup($this->user, $grp, $subject, $message, $params);
				}
			}
		}

		unlink($tmpFile);
		return "201 Created";
	} /* }}} */


	/**
	 * MKCOL method handler
	 *
	 * @param  array  general parameter passing array
	 * @return bool   true on success
	 */
	function MKCOL($options) /* {{{ */
	{
		global $settings;

		$this->log_options('MKCOL', $options);

		$path   = $options["path"];
		$parent = dirname($path);
		$name   = basename($path);

		// get folder from path
		if($parent == '/')
			$parent = '';
		$folder = $this->reverseLookup($parent.'/');

		/* Check if parent folder exists at all */
		if (!$folder) {
			return "409 Conflict";
		}

		/* Check if parent of new folder is a folder */
		if (get_class($folder) != $this->dms->getClassname('folder')) {
			if($this->logger)
				$this->logger->log('MKCOL: access forbidden', PEAR_LOG_ERR);
			return "403 Forbidden";
		}

		/* Check if parent folder already has folder with the same name */
		if ($this->dms->getFolderByName($name, $folder) ) {
			return "405 Method not allowed";
		}

		if (!empty($this->_SERVER["CONTENT_LENGTH"])) { // no body parsing yet
			return "415 Unsupported media type";
		}

		/* Check if user is logged in */
		if(!$this->user) {
			if($this->logger)
				$this->logger->log('MKCOL: access forbidden', PEAR_LOG_ERR);
			return "403 Forbidden";				 
		}

		if ($folder->getAccessMode($this->user, 'addFolder') < M_READWRITE) {
			if($this->logger)
				$this->logger->log('MKCOL: access forbidden', PEAR_LOG_ERR);
			return "403 Forbidden";				 
		}

		$controller = Controller::factory('AddSubFolder');
		$controller->setParam('dms', $this->dms);
		$controller->setParam('user', $this->user);
		$controller->setParam('folder', $folder);
		$controller->setParam('name', $name);
		$controller->setParam('comment', '');
		$controller->setParam('sequence', 0);
		$controller->setParam('attributes', array());
		$controller->setParam('notificationgroups', array());
		$controller->setParam('notificationusers', array());
		if(!$subFolder = $controller->run()) {
//		if (!$folder->addSubFolder($name, '', $this->user, 0)) {
			return "409 Conflict ".$controller->getErrorMsg();				 
		}

		if($this->notifier) {
			if($this->logger)
				$this->logger->log('MKCOL: Sending Notifications', PEAR_LOG_INFO);
			$fnl = $folder->getNotifyList();
			$snl = $subFolder->getNotifyList();
			$nl = array(
				'users'=>array_unique(array_merge($snl['users'], $fnl['users']), SORT_REGULAR),
				'groups'=>array_unique(array_merge($snl['groups'], $fnl['groups']), SORT_REGULAR)
			);

			$subject = "new_subfolder_email_subject";
			$message = "new_subfolder_email_body";
			$params = array();
			$params['name'] = $subFolder->getName();
			$params['folder_name'] = $folder->getName();
			$params['folder_path'] = $folder->getFolderPathPlain();
			$params['username'] = $this->user->getFullName();
			$params['comment'] = '';
			$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewFolder.php?folderid=".$subFolder->getID();
			$params['sitename'] = $settings->_siteName;
			$params['http_root'] = $settings->_httpRoot;
			$this->notifier->toList($this->user, $nl["users"], $subject, $message, $params);
			foreach ($nl["groups"] as $grp) {
				$this->notifier->toGroup($this->user, $grp, $subject, $message, $params);
			}
		}

		return ("201 Created");
	} /* }}} */


	/**
	 * DELETE method handler
	 *
	 * @param  array  general parameter passing array
	 * @return bool   true on success
	 */
	function DELETE($options) /* {{{ */
	{
		global $settings, $fulltextservice;

		$this->log_options('DELETE', $options);

		// get folder or document from path
		$obj = $this->reverseLookup($options["path"]);
		/* Make a second try if it is a directory with the leading '/' */
		if(!$obj)
			$obj = $this->reverseLookup($options["path"].'/');

		// sanity check
		if (!$obj) return "404 Not found";

		// check for access rights
		if($obj->getAccessMode($this->user, get_class($obj) == $this->dms->getClassname('folder') ? 'removeFolder' : 'removeDocument') < M_ALL) {
			if($this->logger)
				$this->logger->log('DELETE: access forbidden', PEAR_LOG_ERR);
			return "403 Forbidden";				 
		}

		if (get_class($obj) == $this->dms->getClassname('folder')) {
			if($obj->hasDocuments() || $obj->hasSubFolders()) {
				if($this->logger)
					$this->logger->log('DELETE: cannot delete, folder has children', PEAR_LOG_ERR);
				return "409 Conflict";
			}

			$parent = $obj->getParent();
			$fnl = $obj->getNotifyList();
			$pnl = $parent->getNotifyList();
			$nl = array(
				'users'=>array_unique(array_merge($fnl['users'], $pnl['users']), SORT_REGULAR),
				'groups'=>array_unique(array_merge($fnl['groups'], $pnl['groups']), SORT_REGULAR)
			);
			$foldername = $obj->getName();

			$controller = Controller::factory('RemoveFolder');
			$controller->setParam('dms', $this->dms);
			$controller->setParam('user', $this->user);
			$controller->setParam('folder', $obj);
			$controller->setParam('fulltextservice', $fulltextservice);
			if(!$controller->run()) {
				return "409 Conflict ".$controller->getErrorMsg();
			}

			if($this->notifier) {
				if($this->logger)
					$this->logger->log('DELETE: Sending Notifications', PEAR_LOG_INFO);
				$subject = "folder_deleted_email_subject";
				$message = "folder_deleted_email_body";
				$params = array();
				$params['name'] = $foldername;
				$params['folder_path'] = $parent->getFolderPathPlain();
				$params['username'] = $this->user->getFullName();
				$params['sitename'] = $settings->_siteName;
				$params['http_root'] = $settings->_httpRoot;
				$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewFolder.php?folderid=".$parent->getID();
				$this->notifier->toList($this->user, $nl["users"], $subject, $message, $params);
				foreach ($nl["groups"] as $grp) {
					$this->notifier->toGroup($this->user, $grp, $subject, $message, $params);
				}
			}
		} else {
			/* Get the notify list before removing the document
			 * Also inform the users/groups of the parent folder
			 */
			$folder = $obj->getFolder();
			$dnl =	$obj->getNotifyList();
			$fnl =	$folder->getNotifyList();
			$nl = array(
				'users'=>array_unique(array_merge($dnl['users'], $fnl['users']), SORT_REGULAR),
				'groups'=>array_unique(array_merge($dnl['groups'], $fnl['groups']), SORT_REGULAR)
			);
			$docname = $obj->getName();

			$controller = Controller::factory('RemoveDocument');
			$controller->setParam('dms', $this->dms);
			$controller->setParam('user', $this->user);
			$controller->setParam('document', $obj);
			$controller->setParam('fulltextservice', $fulltextservice);
			if(!$controller->run()) {
				return "409 Conflict ".$controller->getErrorMsg();
			}

			if($this->notifier){
				if($this->logger)
					$this->logger->log('DELETE: Sending Notifications', PEAR_LOG_INFO);
				$subject = "document_deleted_email_subject";
				$message = "document_deleted_email_body";
				$params = array();
				$params['name'] = $docname;
				$params['folder_path'] = $folder->getFolderPathPlain();
				$params['username'] = $this->user->getFullName();
				$params['sitename'] = $settings->_siteName;
				$params['http_root'] = $settings->_httpRoot;
				$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewFolder.php?folderid=".$folder->getID();
				$this->notifier->toList($this->user, $nl["users"], $subject, $message, $params);
				foreach ($nl["groups"] as $grp) {
					$this->notifier->toGroup($this->user, $grp, $subject, $message, $params);
				}
			}
		}

		return "204 No Content";
	} /* }}} */


	/**
	 * MOVE method handler
	 *
	 * @param  array  general parameter passing array
	 * @return bool   true on success
	 */
	function MOVE($options) /* {{{ */
	{
		global $settings;

		$this->log_options('MOVE', $options);

		// no copying to different WebDAV Servers yet
		if (isset($options["dest_url"])) {
			return "502 bad gateway";
		}

		// get folder or document to move
		$objsource = $this->reverseLookup($options["path"]);
		/* Make a second try if it is directory with the leading '/' */
		if(!$objsource)
			$objsource = $this->reverseLookup($options["path"].'/');
		if (!$objsource)
			return "404 Not found";

		// get dest folder or document
		$objdest = $this->reverseLookup($options["dest"]);

		$newdocname = '';
		/* if the destіnation could not be found, then a folder/document shall
		 * be renamed. In that case the source object is moved into the ѕame
		 * or different folder under a new name.
		 * $objdest will store the new destination folder afterwards
		 */
		if(!$objdest) {
			/* check if at least the dest directory exists */
			$dirname = dirname($options['dest']);
			if($dirname != '/')
				$dirname .= '/';
			$newdocname = basename($options['dest']);
			$objdest = $this->reverseLookup($dirname);
			if(!$objdest)
				return "412 precondition failed";
		}

		/* Moving a document requires write access on the source and
		 * destination object
		 */
		if (($objsource->getAccessMode($this->user) < M_READWRITE) || ($objdest->getAccessMode($this->user) < M_READWRITE)) {
			if($this->logger)
				$this->logger->log('MOVE: access forbidden', PEAR_LOG_ERR);
			return "403 Forbidden";				 
		}

		if(get_class($objdest) == $this->dms->getClassname('document')) {
			/* If destination object is a document it must be overwritten */
			if (!$options["overwrite"]) {
				return "412 precondition failed";
			}
			if(get_class($objsource) == $this->dms->getClassname('folder')) {
				return "400 Bad request";
			}

			/* get the latest content of the source object */
			$content = $objsource->getLatestContent();
			$fspath = $this->dms->contentDir.'/'.$content->getPath();

			/* save the content as a new version in the destination document */
			if(!$objdest->addContent('', $this->user, $fspath, $content->getOriginalFileName(), $content->getFileType(), $content->getMimeType(), array(), array(), 0)) {
				unlink($tmpFile);
				return "409 Conflict";
			}

			/* change the name of the destination object */
			// $objdest->setName($objsource->getName());

			/* delete the source object */
			$objsource->remove();

			return "204 No Content";
		} elseif(get_class($objdest) == $this->dms->getClassname('folder')) {
			/* Set the new Folder of the source object */
			if(get_class($objsource) == $this->dms->getClassname('document')) {
				/* Check if name already exists in the folder */
				if(!$settings->_enableDuplicateDocNames) {
					if($newdocname) {
						if($objdest->hasDocumentByName($newdocname)) {
							return "403 Forbidden";				 
						}
					} else {
						if($objdest->hasDocumentByName($objsource->getName())) {
							return "403 Forbidden";				 
						}
					}
				}

				$oldFolder = $objsource->getFolder();
				if($objsource->setFolder($objdest)) {
					if($this->notifier) {
						if($this->logger)
							$this->logger->log('MOVE: Sending Notifications', PEAR_LOG_INFO);
						$nl1 = $oldFolder->getNotifyList();
						$nl2 = $objsource->getNotifyList();
						$nl3 = $objdest->getNotifyList();
						$nl = array(
							'users'=>array_unique(array_merge($nl1['users'], $nl2['users'], $nl3['users']), SORT_REGULAR),
							'groups'=>array_unique(array_merge($nl1['groups'], $nl2['groups'], $nl3['groups']), SORT_REGULAR)
						);
						$subject = "document_moved_email_subject";
						$message = "document_moved_email_body";
						$params = array();
						$params['name'] = $objsource->getName();
						$params['old_folder_path'] = $oldFolder->getFolderPathPlain();
						$params['new_folder_path'] = $objdest->getFolderPathPlain();
						$params['username'] = $this->user->getFullName();
						$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$objsource->getID();
						$params['sitename'] = $settings->_siteName;
						$params['http_root'] = $settings->_httpRoot;
						$this->notifier->toList($this->user, $nl["users"], $subject, $message, $params);
						foreach ($nl["groups"] as $grp) {
							$this->notifier->toGroup($this->user, $grp, $subject, $message, $params);
						}
					}
				} else {
					return "500 Internal server error";
				}
			} elseif(get_class($objsource) == $this->dms->getClassname('folder')) {
				/* Check if name already exists in the folder */
				if(!$settings->_enableDuplicateSubFolderNames) {
					if($newdocname) {
						if($objdest->hasSubFolderByName($newdocname)) {
							return "403 Forbidden";				 
						}
					} else {
						if($objdest->hasSubFolderByName($objsource->getName())) {
							return "403 Forbidden";				 
						}
					}
				}
				$oldFolder = $objsource->getParent();
				if($objsource->setParent($objdest)) {
					if($this->notifier) {
						if($this->logger)
							$this->logger->log('MOVE: Sending Notifications', PEAR_LOG_INFO);
						$nl1 = $oldFolder->getNotifyList();
						$nl2 = $objsource->getNotifyList();
						$nl3 = $objdest->getNotifyList();
						$nl = array(
							'users'=>array_unique(array_merge($nl1['users'], $nl2['users'], $nl3['users']), SORT_REGULAR),
							'groups'=>array_unique(array_merge($nl1['groups'], $nl2['groups'], $nl3['groups']), SORT_REGULAR)
						);
						$subject = "folder_moved_email_subject";
						$message = "folder_moved_email_body";
						$params = array();
						$params['name'] = $objsource->getName();
						$params['old_folder_path'] = $oldFolder->getFolderPathPlain();
						$params['new_folder_path'] = $objdest->getFolderPathPlain();
						$params['username'] = $this->user->getFullName();
						$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewFolder.php?folderid=".$objsource->getID();
						$params['sitename'] = $settings->_siteName;
						$params['http_root'] = $settings->_httpRoot;
						$this->notifier->toList($this->user, $nl["users"], $subject, $message, $params);
						foreach ($nl["groups"] as $grp) {
							$this->notifier->toGroup($this->user, $grp, $subject, $message, $params);
						}
					}
				} else {
					return "500 Internal server error";
				}
			} else
				return "500 Internal server error";
			if($newdocname)
				$objsource->setName($newdocname);
			return "204 No Content";
		}
	} /* }}} */

	/**
	 * COPY method handler
	 *
	 * @param  array  general parameter passing array
	 * @return bool   true on success
	 */
	function COPY($options) /* {{{ */
	{
		global $settings, $fulltextservice;

		$this->log_options('COPY', $options);

		// TODO Property updates still broken (Litmus should detect this?)

		if (!empty($this->_SERVER["CONTENT_LENGTH"])) { // no body parsing yet
			return "415 Unsupported media type";
		}

		// no copying to different WebDAV Servers yet
		if (isset($options["dest_url"])) {
			return "502 bad gateway";
		}

		// get folder or document to move
		$objsource = $this->reverseLookup($options["path"]);
		/* Make a second try if it is directory with the leading '/' */
		if(!$objsource)
			$objsource = $this->reverseLookup($options["path"].'/');
		if (!$objsource)
			return "404 Not found";

		if (get_class($objsource) == $this->dms->getClassname('folder') && ($options["depth"] != "infinity")) {
			// RFC 2518 Section 9.2, last paragraph
			return "400 Bad request";
		}

		// get dest folder or document
		$objdest = $this->reverseLookup($options["dest"]);

		// If the destination doesn't exists, then check if the parent folder exists
		// and set $newdocname, which is later used to create a new document
		$newdocname = '';
		if(!$objdest) {
			/* check if at least the dest directory exists */
			$dirname = dirname($options['dest']);
			if($dirname != '/')
				$dirname .= '/';
			$newdocname = basename($options['dest']);
			$objdest = $this->reverseLookup($dirname);
			if(!$objdest)
				return "412 precondition failed";
		}

		/* Copying a document requires read access on the source and write
		 * access on the destination object
		 */
		if (($objsource->getAccessMode($this->user) < M_READ) || ($objdest->getAccessMode($this->user) < M_READWRITE)) {
			if($this->logger)
				$this->logger->log('COPY: access forbidden', PEAR_LOG_ERR);
			return "403 Forbidden";				 
		}

		/* If destination object is a document the source document will create a new version */
		if(get_class($objdest) == $this->dms->getClassname('document')) {
			if (!$options["overwrite"]) {
				return "412 precondition failed";
			}
			/* Copying a folder into a document makes no sense */
			if(get_class($objsource) == $this->dms->getClassname('folder')) {
				return "400 Bad request";
			}

			/* get the latest content of the source object */
			$content = $objsource->getLatestContent();
			$fspath = $this->dms->contentDir.'/'.$content->getPath();

			/* If the checksum of source and destination are equal, then do not copy */
			if($content->getChecksum() == $objdest->getLatestContent()->getChecksum()) {
				return "204 No Content";
			}

			/* save the content as a new version in the destination document */
			if(!$objdest->addContent('', $this->user, $fspath, $content->getOriginalFileName(), $content->getFileType(), $content->getMimeType(), array(), array(), 0)) {
				unlink($tmpFile);
				return "409 Conflict";
			}

			/* Since 5.1.13 do not overwrite the name anymore 
				$objdest->setName($objsource->getName()); */

			return "204 No Content";
		} elseif(get_class($objdest) == $this->dms->getClassname('folder')) {
			if($this->logger)
				$this->logger->log('COPY: copy \''.$objsource->getName().'\' to folder '.$objdest->getName().'', PEAR_LOG_INFO);

			/* Currently no support for copying folders */
			if(get_class($objsource) == $this->dms->getClassname('folder')) {
				if($this->logger)
					$this->logger->log('COPY: source is a folder '.$objsource->getName().'', PEAR_LOG_INFO);

				return "400 Bad request";
			}

			if(!$newdocname)
				$newdocname = $objsource->getName();

			/* Check if name already exists in the folder */
			/*
			if(!$settings->_enableDuplicateDocNames) {
				if($objdest->hasDocumentByName($newdocname)) {
					return "403 Forbidden";				 
				}
			}
			 */

			$reviewers = array('i'=>[], 'g'=>[]);
			$approvers = array('i'=>[], 'g'=>[]);
			$workflow = null;
			if($settings->_workflowMode == 'traditional' || $settings->_workflowMode == 'traditional_only_approval') {
				if($settings->_workflowMode == 'traditional') {
					$reviewers = getMandatoryReviewers($objdest, $this->user);
				}
				$approvers = getMandatoryApprovers($objdest, $this->user);
			} elseif($settings->_workflowMode == 'advanced') {
				if($workflows = $user->getMandatoryWorkflows()) {
					$workflow = array_shift($workflows);
				}
			}

			/* get the latest content of the source object */
			$content = $objsource->getLatestContent();
			$fspath = $this->dms->contentDir.'/'.$content->getPath();

			$controller = Controller::factory('AddDocument');
			$controller->setParam('dms', $this->dms);
			$controller->setParam('user', $this->user);
			$controller->setParam('documentsource', 'webdav');
			$controller->setParam('folder', $objdest);
			$controller->setParam('fulltextservice', $fulltextservice);
			$controller->setParam('name', $newdocname);
			$controller->setParam('comment', '');
			$controller->setParam('expires', 0);
			$controller->setParam('keywords', '');
			$controller->setParam('categories', array());
			$controller->setParam('owner', $this->user);
			$controller->setParam('userfiletmp', $fspath);
			$controller->setParam('userfilename', $content->getOriginalFileName());
			$controller->setParam('filetype', $content->getFileType());
			$controller->setParam('userfiletype', $content->getMimeType());
			$minmax = $objdest->getDocumentsMinMax();
			if($settings->_defaultDocPosition == 'start')
				$controller->setParam('sequence', $minmax['min'] - 1);
			else
				$controller->setParam('sequence', $minmax['max'] + 1);
			$controller->setParam('reviewers', $reviewers);
			$controller->setParam('approvers', $approvers);
			$controller->setParam('reqversion', 0);
			$controller->setParam('versioncomment', '');
			$controller->setParam('attributes', array());
			$controller->setParam('attributesversion', array());
			$controller->setParam('workflow', $workflow);
			$controller->setParam('notificationgroups', array());
			$controller->setParam('notificationusers', array());
			$controller->setParam('maxsizeforfulltext', $settings->_maxSizeForFullText);
			$controller->setParam('defaultaccessdocs', $settings->_defaultAccessDocs);
			if(!$document = $controller->run()) {
//			if(!$newdoc = $objdest->addDocument($newdocname, '', 0, $this->user, '', array(), $fspath, $content->getOriginalFileName(), $content->getFileType(), $content->getMimeType(), 0, array(), array(), 0, "")) {
				if($this->logger)
					$this->logger->log('COPY: error copying object', PEAR_LOG_ERR);
				return "409 Conflict";
			}

			if($this->notifier) {
				if($this->logger)
					$this->logger->log('COPY: Sending Notifications', PEAR_LOG_INFO);
				$fnl = $objdest->getNotifyList();
				$dnl = $document->getNotifyList();
				$nl = array(
					'users'=>array_unique(array_merge($dnl['users'], $fnl['users']), SORT_REGULAR),
					'groups'=>array_unique(array_merge($dnl['groups'], $fnl['groups']), SORT_REGULAR)
				);

				$subject = "new_document_email_subject";
				$message = "new_document_email_body";
				$params = array();
				$params['name'] = $newdocname;
				$params['folder_name'] = $objdest->getName();
				$params['folder_path'] = $objdest->getFolderPathPlain();
				$params['username'] = $this->user->getFullName();
				$params['comment'] = '';
				$params['version_comment'] = '';
				$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
				$params['sitename'] = $settings->_siteName;
				$params['http_root'] = $settings->_httpRoot;
				$this->notifier->toList($this->user, $nl["users"], $subject, $message, $params);
				foreach ($nl["groups"] as $grp) {
					$this->notifier->toGroup($this->user, $grp, $subject, $message, $params);
				}
			}
			return "201 Created";
		}
	} /* }}} */

	/**
	 * PROPPATCH method handler
	 *
	 * @param  array  general parameter passing array
	 * @return bool   true on success
	 */
	function PROPPATCH(&$options) /* {{{ */
	{
		$this->log_options('PROPPATCH', $options);

		// get folder or document from path
		$obj = $this->reverseLookup($options["path"]);

		// sanity check
		if (!$obj) {
			$obj = $this->reverseLookup($options["path"].'/');
			if(!$obj)
				return false;
		}

		if ($obj->getAccessMode($this->user) < M_READWRITE) {
			return false;				 
		}

		foreach ($options["props"] as $key => $prop) {
			if ($prop["ns"] == "DAV:") {
				$options["props"][$key]['status'] = "403 Forbidden";
			} else {
				$this->logger->log('PROPPATCH: set '.$prop["ns"].''.$prop["val"].' to '.$prop["val"], PEAR_LOG_INFO);
				if($prop["ns"] == "SeedDMS:") {
					if(in_array($prop['name'], array('id', 'version', 'status', 'status-comment', 'status-date'))) {
						$options["props"][$key]['status'] = "403 Forbidden";
					} else {
						if (isset($prop["val"]))
							$val = $prop["val"];
						else
							$val = '';
						switch($prop["name"]) {
						case "comment":
							$obj->setComment($val);
							break;
						case "expires":
							if($obj->isType("document")) {
								if($val) {
									$ts = strtotime($val);
									if($ts !== false) {
										if(!$obj->setExpires($ts))
											return false;
									} else {
										$options["props"][$key]['status'] = "400 Could not parse date";
										return false;
									}
								} else {
									$obj->setExpires(0);
								}
							} else {
								$options["props"][$key]['status'] = "405 Expiration date cannot be set on folders";
								return false;
							}
							break;
						default:
							if($attrdef = $this->dms->getAttributeDefinitionByName($prop["name"])) {
								$valueset = $attrdef->getValueSetAsArray();
								switch($attrdef->getType()) {
								case SeedDMS_Core_AttributeDefinition::type_string:
									$obj->setAttributeValue($attrdef, $val);
									break;
								case SeedDMS_Core_AttributeDefinition::type_int:
									$obj->setAttributeValue($attrdef, (int) $val);
									break;
								case SeedDMS_Core_AttributeDefinition::type_float:
									$obj->setAttributeValue($attrdef, (float) $val);
									break;
								case SeedDMS_Core_AttributeDefinition::type_boolean:
									$obj->setAttributeValue($attrdef, $val == 1 ? true : false);
									break;
								}
							}
						}
					}
				}
			}
		}

		return true;
	} /* }}} */


	/**
	 * LOCK method handler
	 *
	 * @param  array  general parameter passing array
	 * @return bool   true on success
	 */
	function LOCK(&$options) /* {{{ */
	{
		$this->log_options('LOCK', $options);

		// get object to lock
		$obj = $this->reverseLookup($options["path"]);

		if(!$obj)
			return "200 OK";

		// TODO recursive locks on directories not supported yet
		if (get_class($obj) == $this->dms->getClassname('folder') && !empty($options["depth"])) {
			return "409 Conflict";
		}

		if ($obj->getAccessMode($this->user) < M_READWRITE) {
			if($this->logger)
				$this->logger->log('LOCK: access forbidden', PEAR_LOG_ERR);
			return "403 Forbidden";				 
		}

		$options["timeout"] = 0;//time()+300; // 5min. hardcoded

		if(!$obj->setLocked($this->user)) {
			return "409 Conflict";
		}

		$options['owner'] = $this->user->getLogin();
		$options['scope'] = "exclusive";
		$options['type'] = "write";

		return "200 OK";
	} /* }}} */

	/**
	 * UNLOCK method handler
	 *
	 * @param  array  general parameter passing array
	 * @return bool   true on success
	 */
	function UNLOCK(&$options) /* {{{ */
	{
		$this->log_options('UNLOCK', $options);

		// get object to unlock
		$obj = $this->reverseLookup($options["path"]);

		if(!$obj)
			return "204 No Content";

		// TODO recursive locks on directories not supported yet
		if (get_class($obj) == $this->dms->getClassname('folder') && !empty($options["depth"])) {
			return "409 Conflict";
		}

		if ($obj->getAccessMode($this->user) < M_READWRITE) {
			if($this->logger)
				$this->logger->log('UNLOCK: access forbidden', PEAR_LOG_ERR);
			return "403 Forbidden";				 
		}

		if(!$obj->setLocked(false)) {
			return "409 Conflict";
		}

		return "204 No Content";
	} /* }}} */

	/**
	 * checkLock() helper
	 *
	 * @param  string resource path to check for locks
	 * @return bool   true on success
	 */
	function checkLock($path) /* {{{ */
	{
		if($this->logger)
			$this->logger->log('checkLock: path='.$path.'', PEAR_LOG_INFO);

		// get object to check for lock
		$obj = $this->reverseLookup($path);

		// check for folder returns no object
		if(!$obj) {
			if($this->logger)
				$this->logger->log('checkLock: object not found', PEAR_LOG_INFO);
			return false;
		}

		// Folders cannot be locked
		if(get_class($obj) == $this->dms->getClassname('folder')) {
			if($this->logger)
				$this->logger->log('checkLock: object is a folder', PEAR_LOG_INFO);
			return false;
		}

		if($obj->isLocked() && $this->user->getLogin() != $obj->getLockingUser()->getLogin()) {
			$lockuser = $obj->getLockingUser();
			if($this->logger)
				$this->logger->log('checkLock: object is locked by '.$lockuser->getLogin(), PEAR_LOG_INFO);
			return array(
				"type"    => "write",
				"scope"   => "exclusive",
				"depth"   => 0,
				"owner"   => $lockuser->getLogin(),
				"token"   => 'kk', // must return something to prevent php warning in Server.php:1865
				"created" => '',
				"modified" => '',
				"expires" => ''
				);
		} else {
			if($this->logger)
				$this->logger->log('checkLock: object is not locked', PEAR_LOG_INFO);
			return false;
		}
	} /* }}} */

}


/*
 * vim: ts=2 sw=2 noexpandtab
 */
?>
