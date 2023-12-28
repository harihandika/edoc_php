<?php

/**
 * Implementation of notifation system using email
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
require_once("inc.ClassNotify.php");
require_once("Mail.php");

/**
 * Class to send email notifications to individuals or groups
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_EmailNotify extends SeedDMS_Notify
{
	/**
	 * Instanz of DMS
	 */
	protected $_dms;

	protected $smtp_server;

	protected $smtp_port;

	protected $smtp_user;

	protected $smtp_password;

	protected $from_address;

	protected $lazy_ssl;

	protected $debug;

	function __construct($dms, $from_address = '', $smtp_server = '', $smtp_port = '', $smtp_username = '', $smtp_password = '', $lazy_ssl = true)
	{ /* {{{ */
		$this->_dms = $dms;
		$this->smtp_server = $smtp_server;
		$this->smtp_port = $smtp_port;
		$this->smtp_user = $smtp_username;
		$this->smtp_password = $smtp_password;
		$this->from_address = $from_address;
		$this->lazy_ssl = $lazy_ssl;
		$this->debug = false;
	} /* }}} */

	public function setDebug($debug = true)
	{ /* {{{ */
		$this->debug = (bool) $debug;
	} /* }}} */

	/**
	 * Send mail to individual user
	 *
	 * @param mixed $sender individual sending the email. This can be a
	 *        user object or a string. If it is left empty, then
	 *        $this->from_address will be used.
	 * @param object $recipient individual receiving the mail
	 * @param string $subject key of string containing the subject of the mail
	 * @param string $message key of string containing the body of the mail
	 * @param array $params list of parameters which replaces placeholder in
	 *        the subject and body
	 *        This may contain the special keys
	 *        __lang__: use this language for the email
	 *        __skip_header__: do not include the standard body header
	 *        __skip_footer__: do not include the standard body footer
	 * @param array $attachments list of attachments
	 * @return false or -1 in case of error, otherwise true
	 */

	function notiftemplate($penerima, $statusheader, $statusnotif, $params = array()){
		$ntfheader = "Dear Pak/Bu " . $penerima  . "<br>"."<br>";
		$ntffooter = "Silahkan klik link ini untuk melihat Document: <a href=\"https://edoc.acset.co/#/login\">https://edoc.acset.co/#/login</a>" . "<br>" . "<br>"
			. "<b>" . "Best Regards" . "<b>" . "<br>" . "<br>" . "<br>"
			. "ACSET Electronic Document System Team";
		$return = $ntfheader
				. " Kami informasikan terdapat ". $statusheader . "<br>" . "<br>"
				. "<b>" . "Nama Project		: " . $params["projectname"] . "</b>" . "<br>"
				. "<b>" . "Category			: " . $params["category"] . "</b>" . "<br>"
				. "<b>" . "Nama File		: " . $params["name"] . "</b>" . "<br>"
				. "<b>" . "Status Document	: " . $statusnotif . "</b>" . "<br>" . "<br>"
				. "Email ini otomatis terkirim berdasarkan proses dari dokumen yang sudah dilakukan secara bertahap, untuk melihat informasi detail" . "<br>" . "<br>"
				. $ntffooter;
		return $return;
	}

	//  recipient sudah benar
	function toIndividual($sender, $recipient, $subject, $messagekey, $params = array(), $attachments = array())
	{ /* {{{ */
		if (is_object($recipient) && $recipient->isType('user') && !$recipient->isDisabled() && $recipient->getEmail() != "") {
			$to = $recipient->getEmail();
			$lang = $recipient->getLanguage();
		} elseif (is_string($recipient) && trim($recipient) != "") {
			$to = $recipient;
			if (isset($params['__lang__']))
				$lang = $params['__lang__'];
			else
				$lang = 'en_GB';
		} else {
			return false;
		}

		if (!$to)
			return false;

		$returnpath = $this->from_address;
		if (is_object($sender) && !strcasecmp(get_class($sender), $this->_dms->getClassname('user'))) {
			// $from = $sender->getFullName() ." <". $sender->getEmail() .">";
			$from = 'Administrator' . " <" . $returnpath . ">";
			// if(!$returnpath)
			// 	$returnpath = $sender->getEmail();
			// } elseif(is_string($sender) && trim($sender) != "") {
			// 	$from = $sender;
			// 	if(!$returnpath)
			// 		$returnpath = $sender;
			// } else {
			// $from = $this->from_address;
		}


		$body = '';
		if (!isset($params['__skip_header__']) || !$params['__skip_header__']) {
			if (!isset($params['__header__']))
				$body .= getMLText("email_header", $params, "", $lang) . "\r\n\r\n";
			elseif ($params['__header__'])
				$body .= getMLText($params['__header__'], $params, "", $lang) . "\r\n\r\n";
		}
		if (isset($params['__body__']))
			$body .= $params['__body__'];
		else
			$body .= getMLText($messagekey, $params, "", $lang);
		// if(!isset($params['__skip_footer__']) || !$params['__skip_footer__']) {
		// 	if(!isset($params['__footer__']))
		// 		$body .= "\r\n\r\n".getMLText("email_footer", $params, "", $lang);
		// 	elseif($params['__footer__'])
		// 		$body .= "\r\n\r\n".getMLText($params['__footer__'], $params, "", $lang);
		// }

		$statusdoc = '';
		if ($params['status'] == 1) {
			$statusdoc = 'Draft - Pending Approval';
		} else if ($params['status'] == 2) {
			$statusdoc = 'Released';
		} else if ($params['status'] == 3) {
			$statusdoc = 'In Workflow';
		} else if ($params['status'] == 4) {
			$statusdoc = 'In Revision';
		} else if ($params['status'] == 5) {
			$statusdoc = 'Draft';
		} else if ($params['status'] == 0) {
			$statusdoc = 'Draft - Pending Review';
		} else if ($params['status'] == -1) {
			$statusdoc = 'Rejected';
		} else if ($params['status'] == -2) {
			$statusdoc = 'Obsolete';
		}

		$bodyhtml = '';
		if (isset($params['__body_html__']) || getMLText($messagekey . '_html')) {
			if (!isset($params['__skip_header__']) || !$params['__skip_header__']) {
				if (!isset($params['__header_html__']) && $params['assignnotif'] == '') {
					switch($subject){
						case 'new_document_email_subject':
							$penerima = /*$params['assignnotif'];*/'';
							$statusheader ='sudah dilakukan proses submit dengan spesifikasi dokumennya sebagai berikut:';
							$statusnotif = $statusdoc . " - [" . $params['username'] . "]";
							$template = $this->notiftemplate($penerima, $statusheader, $statusnotif, $params);
							$bodyhtml .= $template . "\r\n\r\n";
							break;
						// case 'document_updated_email_subject':
						// 	$bodyhtml .= "update document" . "\r\n\r\n";
						// 	break;
						case 'review_request_email_subject':
							$penerima = $params['review'];
							$statusheader='yang membutuhkan <b>Review</b> dari anda dengan spesifikasi dokumennya sebagai berikut:';
							$statusnotif = 'Requesting Review';
							$template = $this->notiftemplate($penerima, $statusheader, $statusnotif, $params);
							$bodyhtml .= $template . "\r\n\r\n";
							break;
						case 'approval_request_email_subject':
							$penerima = $recipient->getFullName();
							$statusheader='yang membutuhkan <b>Approval</b> dari anda dengan spesifikasi dokumennya sebagai berikut:';
							$statusnotif = 'Requesting Approval';
							$template = $this->notiftemplate($penerima, $statusheader, $statusnotif, $params);
							$bodyhtml .= $template . "\r\n\r\n";
							break;
						case 'expired_document_email_subject':
							$penerima = $params['username'];
							$statusheader='yang masa berlakunya akan habis <b>(Expired)</b> dengan spesifikasi dokumennya sebagai berikut:';
							$statusnotif = $params['exp'];
							$template = $this->notiftemplate($penerima, $statusheader, $statusnotif, $params);
							$bodyhtml .= $template . "\r\n\r\n";
							break;
						// case 'approval_submit_email_subject':
						//  	$bodyhtml .= "approved" . "\r\n\r\n";
						// 	break;
						// case 'review_submit_email_subject':
						// 	$bodyhtml .= "reviewed" . "\r\n\r\n";
						// 	break;
						// case 'notify_deleted_email_subject':
						// 	$bodyhtml.=	"document deleted" . "\r\n\r\n";
						// 	break;
						// case 'document_renamed_email_subject':
						// 	$bodyhtml .= "document edit" . "\r\n\r\n";
						// 	break;
						// case 'document_status_changed_email_subject':
						// 	$bodyhtml .= "document edit" . "\r\n\r\n";
						// 	break;
						// case 'version_deleted_email_subject':
						// 	$bodyhtml .= "document version deleted" . "\r\n\r\n";
						// 	break;
						case 'send_login_data_subject':
							$bodyhtml .= "\r\n\r\n" . getMLText($messagekey, $params, "", $lang) . "\r\n\r\n";
							break;
						default: 
							return 0;
							break;
					}					
				}else if(!isset($params['__header_html__']) && $params['assignnotif'] != ''){
					// penerima bukan email, tapi subjek
					$penerima = $params['assignnotif'];
					switch($subject){
						case 'new_document_email_subject':							
							$statusheader ='sudah dilakukan proses submit dengan spesifikasi dokumennya sebagai berikut:';
							$statusnotif = $statusdoc . " - [" . $params['username'] . "]";
							$template = $this->notiftemplate($penerima, $statusheader, $statusnotif, $params);
							$bodyhtml .= $template . "\r\n\r\n";
							break;
						// case 'document_updated_email_subject':
						// 	$bodyhtml .= "update document" . "\r\n\r\n";
						// 	break;
						case 'review_request_email_subject':							
							$statusheader='yang membutuhkan <b>Review</b> dari anda dengan spesifikasi dokumennya sebagai berikut:';
							$statusnotif = 'Requesting Review';
							$template = $this->notiftemplate($penerima, $statusheader, $statusnotif, $params);
							$bodyhtml .= $template . "\r\n\r\n";
							break;
						case 'approval_request_email_subject':
							$statusheader='yang membutuhkan <b>Approval</b> dari anda dengan spesifikasi dokumennya sebagai berikut:';
							$statusnotif= 'Requesting Approval';
							$template = $this->notiftemplate($penerima, $statusheader, $statusnotif, $params);
							$bodyhtml .= $template . "\r\n\r\n";
							break;
						case 'expired_document_email_subject':
							$penerima = $params['username'];
							$statusheader='yang masa berlakunya akan habis <b>(Expired)</b> dengan spesifikasi dokumennya sebagai berikut:';
							$statusnotif = $params['exp'];
							$template = $this->notiftemplate($penerima, $statusheader, $statusnotif, $params);
							$bodyhtml .= $template . "\r\n\r\n";
							break;
						// case 'approval_submit_email_subject':
						//  	$bodyhtml .= "approved" . "\r\n\r\n";
						// 	break;
						// case 'review_submit_email_subject':
						// 	$bodyhtml .= "reviewed" . "\r\n\r\n";
						// 	break;
						// case 'notify_deleted_email_subject':
						// 	$bodyhtml.=	"document deleted" . "\r\n\r\n";
						// 	break;
						// case 'document_renamed_email_subject':
						// 	$bodyhtml .= "document edit" . "\r\n\r\n";
						// 	break;
						// case 'document_status_changed_email_subject':
						// 	$bodyhtml .= "document edit" . "\r\n\r\n";
						// 	break;
						// case 'version_deleted_email_subject':
						// 	$bodyhtml .= "document version deleted" . "\r\n\r\n";
						// 	break;
						case 'send_login_data_subject':
							$bodyhtml .= "\r\n\r\n" . getMLText($messagekey, $params, "", $lang) . "\r\n\r\n";
							break;
						default: 
							return 0;
							break;
					}
				}
				elseif ($params['__header_html__'])
					$bodyhtml .= getMLText($params['__header_html__'], $params, "", $lang) . "\r\n\r\n";
			}
			// if(isset($params['__body_html__']))
			// 	$bodyhtml .= $params['__body_html__'];
			// else
			// 	$bodyhtml .= getMLText($messagekey.'_html', $params, "", $lang);
			// if(!isset($params['__skip_footer__']) || !$params['__skip_footer__']) {
			// 	if(!isset($params['__footer_html__']))
			// 		$bodyhtml .= "\r\n\r\n".getMLText("email_footer_html", $params, "", $lang);
			// 	elseif($params['__footer_html__'])
			// 		$bodyhtml .= "\r\n\r\n".getMLText($params['__footer_html__'], $params, "", $lang);
			// }
		}
		

		
		// templating

		$mime = new Mail_mime(array('eol' => "\n"));

		$mime->setTXTBody($body);
		if ($bodyhtml)
			$mime->setHTMLBody($bodyhtml);

		if ($attachments) {
			foreach ($attachments as $attachment) {
				if (!$mime->addAttachment(
					$attachment['file'],
					$attachment['mimetype'],
					isset($attachment['name']) ? $attachment['name'] : '',
					isset($attachment['isfile']) ? $attachment['isfile'] : true
				)) {
					return false;
				}
			}
		}

		$message = $mime->get(array(
			'text_encoding' => '8bit',
			'html_encoding' => '8bit',
			'head_charset' => 'utf-8',
			'text_charset' => 'utf-8',
			'html_charset' => 'utf-8'
		));

		$headers = array();
		$headers['From'] = 'Administrator' . '<' . $returnpath . '>';
		if ($returnpath)
			$headers['Return-Path'] = $returnpath;
		$headers['To'] = $to;
		$preferences = array("input-charset" => "UTF-8", "output-charset" => "UTF-8");
		$encoded_subject = iconv_mime_encode("Subject", getMLText($subject, $params, "", $lang), $preferences);
		$headers['Subject'] = substr($encoded_subject, strlen('Subject: '));
		$headers['Date'] = date('r', time());
		$headers['MIME-Version'] = "1.0";
		//		$headers['Content-type'] = "text/plain; charset=utf-8";

		$hdrs = $mime->headers($headers);

		$mail_params = array();
		if ($this->smtp_server) {
			if ($this->debug)
				$mail_params['debug'] = true;
			$mail_params['host'] = $this->smtp_server;
			if ($this->smtp_port) {
				$mail_params['port'] = $this->smtp_port;
			}
			if ($this->smtp_user) {
				$mail_params['auth'] = true;
				$mail_params['username'] = $this->smtp_user;
				$mail_params['password'] = $this->smtp_password;
			}
			/* See ticket #384 */
			if ($this->lazy_ssl)
				$mail_params['socket_options'] = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false));

			$mail = Mail::factory('smtp', $mail_params);
		} else {
			$mail = Mail::factory('mail', $mail_params);
		}

		if (isset($GLOBALS['SEEDDMS_HOOKS']['mailqueue'])) {
			foreach ($GLOBALS['SEEDDMS_HOOKS']['mailqueue'] as $queueService) {
				if (method_exists($queueService, 'queueMailJob')) {
					$ret = $queueService->queueMailJob($mail_params, $to, $hdrs, getMLText($subject, $params, "", $lang), $message);
					if ($ret !== null)
						return $ret;
				}
			}
		}
		$result = $mail->send($to, $hdrs, $message);
		if (PEAR::isError($result)) {
			return false;
		} else {
			return true;
		}
	} /* }}} */

	/**
	 * This method is deprecated!
	 *
	 * The dispatching is now done in SeedDMS_NotificationService::toGroup()
	 */
	function toGroup($sender, $groupRecipient, $subject, $message, $params = array())
	{ /* {{{ */
		if ((!is_object($sender) && strcasecmp(get_class($sender), $this->_dms->getClassname('user'))) ||
			(!is_object($groupRecipient) || strcasecmp(get_class($groupRecipient), $this->_dms->getClassname('group')))
		) {
			return false;
		}

		foreach ($groupRecipient->getUsers() as $recipient) {
			$this->toIndividual($sender, $recipient, $subject, $message, $params);
		}

		return true;
	} /* }}} */

	/**
	 * This method is deprecated!
	 *
	 * The dispatching is now done in SeedDMS_NotificationService::toList()
	 */
	function toList($sender, $recipients, $subject, $message, $params = array())
	{ /* {{{ */
		if ((!is_object($sender) && strcasecmp(get_class($sender), $this->_dms->getClassname('user'))) ||
			(!is_array($recipients) && count($recipients) == 0)
		) {
			return false;
		}

		$ret = true;
		foreach ($recipients as $recipient) {
			$ret &= $this->toIndividual($sender, $recipient, $subject, $message, $params);
		}

		return $ret;
	} /* }}} */

	
}
