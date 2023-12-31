<?php
/**
 * Implementation of Login view
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
 * Class which outputs the html page for Login view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Login extends SeedDMS_Bootstrap_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
?>
document.form1.login.focus();
function checkForm()
{
	msg = new Array()
	if($("#login").val() == "") msg.push("<?php printMLText("js_no_login");?>");
	if($("#pwd").val() == "") msg.push("<?php printMLText("js_no_pwd");?>");
	if (msg != "") {
  	noty({
  		text: msg.join('<br />'),
  		type: 'error',
      dismissQueue: true,
  		layout: 'topRight',
  		theme: 'defaultTheme',
			_timeout: 1500,
  	});
		return false;
	}
	else
		return true;
}

function guestLogin()
{
	theme = $("#themeselector").val();
	lang = $("#languageselector").val();
	url = "../op/op.Login.php?login=guest";
	if(theme)
		url += "&sesstheme=" + theme;
	if(lang)
		url += "&lang=" + lang;
	if (document.form1.referuri) {
		url += "&referuri=" + escape(document.form1.referuri.value);
	}
	document.location.href = url;
}
$(document).ready( function() {
/*
	$('body').on('submit', '#form', function(ev){
		if(checkForm()) return;
		ev.preventDefault();
	});
*/
	$('body').on('click', '#guestlogin', function(ev){
		ev.preventDefault();
		guestLogin();
	});
	$("#form").validate({
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
		messages: {
			login: "<?php printMLText("js_no_login");?>",
			pwd: "<?php printMLText("js_no_pwd");?>"
		},
	});
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$enableguestlogin = $this->params['enableguestlogin'];
		$enablepasswordforgotten = $this->params['enablepasswordforgotten'];
		$refer = $this->params['referrer'];
		$themes = $this->params['themes'];
		$msg = $this->params['msg'];
		$languages = $this->params['languages'];
		$enableLanguageSelector = $this->params['enablelanguageselector'];
		$enableThemeSelector = $this->params['enablethemeselector'];
		$enable2factauth = $this->params['enable2factauth'];

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("login_page"), "login");
		// $this->globalBanner();
		// $this->contentStart();
		// $this->pageNavigation(getMLText("sign_in"));
?>
<?php 
	// $this->contentContainerStart();
	$this->loginPageAcset();
	if($msg)
			$this->failedLogin(htmlspecialchars($msg));
?>

<form class="form-horizontal" action="../op/op.Login.php" method="post" name="form1" id="form">
<?php
		if ($refer) {
			echo "<input type='hidden' name='referuri' value='".sanitizeString($refer)."'/>";
		}
		$this->formField(
			'Username',
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'login',
				'name'=>'login',
				'class'=>'form-control',
				'placeholder'=>'enter your username',
				'autocomplete'=>'off',
				'required'=>true
			)
		);
		$this->formField(
			getMLText("password"),
			array(
				'element'=>'input',
				'type'=>'password',
				'id'=>'pwd',
				'name'=>'pwd',
				'class'=>'form-control',
				'placeholder'=>'enter your password',
				'autocomplete'=>'off',
				'required'=>true
			)
		);
		if($enable2factauth) {
			require "vendor/robthree/twofactorauth/lib/Providers/Qr/IQRCodeProvider.php";
			require "vendor/robthree/twofactorauth/lib/Providers/Qr/BaseHTTPQRCodeProvider.php";
//			require "vendor/robthree/twofactorauth/lib/Providers/Qr/GoogleQRCodeProvider.php";
			require "vendor/robthree/twofactorauth/lib/Providers/Rng/IRNGProvider.php";
			require "vendor/robthree/twofactorauth/lib/Providers/Rng/MCryptRNGProvider.php";
			require "vendor/robthree/twofactorauth/lib/TwoFactorAuthException.php";
			require "vendor/robthree/twofactorauth/lib/TwoFactorAuth.php";
			$tfa = new \RobThree\Auth\TwoFactorAuth('SeedDMS');
			$this->formField(
				getMLText("2_factor_auth"),
				'<input type="Password" id="twofactauth" name="twofactauth" value="" />'
			);
		}
		if($enableLanguageSelector) {
			$html = "<select id=\"languageselector\" name=\"lang\">";
			$html .= "<option value=\"\">-";
			foreach ($languages as $currLang) {
				$html .= "<option value=\"".$currLang."\">".getMLText($currLang)."</option>";
			}
			$html .= "</select>";
			$this->formField(
				getMLText("language"),
				$html
			);
		}
		if($enableThemeSelector) {
			$html = "<select id=\"themeselector\" name=\"sesstheme\">";
			$html .= "<option value=\"\">-";
			foreach ($themes as $currTheme) {
				$html .= "<option value=\"".$currTheme."\">".$currTheme;
			}
			$html .= "</select>";
			$this->formField(
				getMLText("theme"),
				$html
			);
		}
		$this->formSubmit(getMLText('submit_login'));
		if ($enablepasswordforgotten)
			echo "<a href=\"../out/out.PasswordForgotten.php\">" . getMLText("password_forgotten") . "</a>\n";
?>
</form>
<?php
		$this->endLoginPageAcset();
		// $this->contentContainerEnd();
		$tmpfoot = array();
		if ($enableguestlogin)
			$tmpfoot[] = "<a href=\"\" id=\"guestlogin\">" . getMLText("guest_login") . "</a>\n";
		if($tmpfoot) {
			print "<p>";
			print implode(' | ', $tmpfoot);
			print "</p>\n";
		}
		// $this->contentEnd();
		// $this->htmlEndPage();
		echo '</body></html>';
	} /* }}} */
}
?>
