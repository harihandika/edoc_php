<?php
/**
 * Implementation of PasswordForgotten view
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
 * Class which outputs the html page for PasswordForgotten view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_PasswordForgotten extends SeedDMS_Bootstrap_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
?>
function checkForm()
{
	msg = new Array();
	if (document.form1.login.value == "") msg.push("<?php printMLText("js_no_login");?>");
	if (document.form1.email.value == "") msg.push("<?php printMLText("js_no_email");?>");
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
$(document).ready(function() {
	$('body').on('submit', '#form1', function(ev){
		if(checkForm()) return;
		ev.preventDefault();
	});
});
document.form1.email.focus();
<?php
	} /* }}} */

	function show() { /* {{{ */
		$referrer = $this->params['referrer'];

		$this->htmlStartPage(getMLText("password_forgotten"), "passwordforgotten");
		$this->globalBanner();
		$this->contentStart();
		$this->pageNavigation(getMLText("password_forgotten"));
?>

<?php $this->contentContainerStart(); ?>
<form class="form-horizontal" action="../op/op.PasswordForgotten.php" method="post" id="form1" name="form1">
<?php
		if ($referrer) {
			echo "<input type='hidden' name='referuri' value='".$referrer."'/>";
		}

echo '<p>';
printMLText("password_forgotten_text");
echo '</p>';

		$this->formField(
			getMLText("user_login"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'login',
				'name'=>'login',
				'placeholder'=>'login',
				'autocomplete'=>'off',
				'required'=>true
			)
		);
		$this->formField(
			getMLText("email"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'email',
				'name'=>'email',
				'placeholder'=>'email',
				'autocomplete'=>'off',
				'required'=>true
			)
		);
		$this->forgotSubmit(getMLText('submit_password_forgotten'));
?>

<div class="control-group">
	<p class="control-label"><a href="../out/out.Login.php"><?php echo getMLText("login"); ?></a></p>
</div>

</form>
<?php $this->contentContainerEnd(); ?>
<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
