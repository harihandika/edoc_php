<?php
/**
 * Implementation of MyDocuments view
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
 * Include class to preview documents
 */
require_once("SeedDMS/Preview.php");

/**
 * Class which outputs the html page for MyDocuments view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Tasks extends SeedDMS_Bootstrap_Style {

	function js() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];

		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('cancel', 'splash_move_document', 'confirm_move_document', 'move_document', 'confirm_transfer_link_document', 'transfer_content', 'link_document', 'splash_move_folder', 'confirm_move_folder', 'move_folder'));
		$this->printClickDocumentJs();
?>
$(document).ready( function() {
	$('body').on('click', 'ul.bs-docs-sidenav li a', function(ev){
		ev.preventDefault();
		$('#kkkk.ajax').data('action', $(this).data('action'));
		$('#kkkk.ajax').trigger('update', {orderby: $(this).data('orderby')});
	});
	$('body').on('click', 'table th a', function(ev){
		ev.preventDefault();
		$('#kkkk.ajax').data('action', $(this).data('action'));
		$('#kkkk.ajax').trigger('update', {orderby: $(this).data('orderby'), orderdir: $(this).data('orderdir')});
	});
});
<?php
	} /* }}} */

	protected function printListHeader($resArr, $previewer, $action=false) { /* {{{ */
		$orderby = $this->params['orderby'];
		$orderdir = $this->params['orderdir'];

		print "<table class=\"table table-condensed\">";
		print "<thead>\n<tr>\n";
		print "<th></th>\n";
		if($action) {
			print "<th>";
			print "<a data-action=\"".$action."\" data-orderby=\"n\" data-orderdir=\"".($orderdir == 'desc' ? '' : 'desc')."\">".getMLText("name")."</a> ".($orderby == 'n' || $orderby == '' ? ($orderdir == 'desc' ? '<i class="fa fa-arrow-up"></i>' :  '<i class="fa fa-arrow-down"></i>') : '')." &middot; ";
			print "<a data-action=\"".$action."\" data-orderby=\"u\" data-orderdir=\"".($orderdir == 'desc' ? '' : 'desc')."\">".getMLText("last_update")."</a> ".($orderby == 'u' ? ($orderdir == 'desc' ? '<i class="fa fa-arrow-up"></i>' :  '<i class="fa fa-arrow-down"></i>') : '')." &middot; ";
			print "<a data-action=\"".$action."\" data-orderby=\"e\" data-orderdir=\"".($orderdir == 'desc' ? '' : 'desc')."\">".getMLText("expires")."</a> ".($orderby == 'e' ? ($orderdir == 'desc' ? '<i class="fa fa-arrow-up"></i>' :  '<i class="fa fa-arrow-down"></i>') : '');
			print "</th>\n";
		} else
			print "<th>".getMLText("name")."</th>\n";
		if($action)
			print "<th><a data-action=\"".$action."\" data-orderby=\"s\" data-orderdir=\"".($orderdir == 'desc' ? '' : 'desc')."\">".getMLText("status")."</a>".($orderby == 's' ? " ".($orderdir == 'desc' ? '<i class="fa fa-arrow-up"></i>' :  '<i class="fa fa-arrow-down"></i>') : '')."</th>\n";
		else
			print "<th>".getMLText("status")."</th>\n";
		print "<th>".getMLText("action")."</th>\n";
		print "</tr>\n</thead>\n<tbody>\n";
	} /* }}} */

	protected function printListFooter() { /* {{{ */
		echo "</tbody>\n</table>";
	} /* }}} */

	protected function printList($resArr, $previewer, $action=false) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];

		$this->printListHeader($resArr, $previewer, $action);
		$noaccess = 0;
		$docs = [];
		foreach ($resArr as $res) {
			if($document = $dms->getDocument($res["id"])) {
				$document->verifyLastestContentExpriry();

				if($document->getAccessMode($user) >= M_READ && $document->getLatestContent()) {
					$docs[] = $document;
				} else {
					$noaccess++;
				}
			}
		}
		if($this->hasHook('filterList'))
			$docs = $this->callHook('filterList', $docs, $action);
		foreach($docs as $document) {
			$txt = $this->callHook('documentListItem', $document, $previewer, false);
			if(is_string($txt))
				echo $txt;
			else
				echo $this->documentListRow($document, $previewer, false);
		}
		$this->printListFooter();

		if($noaccess) {
			$this->warningMsg(getMLText('list_contains_no_access_docs', array('count'=>$noaccess)));
		}
	} /* }}} */

	function listDocsToLookAt() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$orderby = $this->params['orderby'];
		$orderdir = $this->params['orderdir'];
		$workflowmode = $this->params['workflowmode'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		$previewer->setConverters($previewconverters);

		if($workflowmode != 'advanced') {
			/* Get list of documents owned by current user that are
			 * pending review or pending approval.
			 */
			$resArr = $dms->getDocumentList('AppRevOwner', $user, false, $orderby, $orderdir);
			if (is_bool($resArr) && !$resArr) {
				$this->contentHeading(getMLText("warning"));
				$this->contentContainer(getMLText("internal_error_exit"));
				$this->htmlEndPage();
				exit;
			}

			$this->contentHeading(getMLText("documents_user_requiring_attention"));
			if ($resArr) {
				$this->printList($resArr, $previewer, 'listDocsToLookAt');
			} else {
				printMLText("no_docs_to_look_at");
			}
		} else {
			$resArr = $dms->getDocumentList('WorkflowOwner', $user, false, $orderby, $orderdir);
			if (is_bool($resArr) && !$resArr) {
				$this->contentHeading(getMLText("warning"));
				$this->contentContainer("Internal error. Unable to complete request. Exiting.");
				$this->htmlEndPage();
				exit;
			}

			$this->contentHeading(getMLText("documents_user_requiring_attention"));
			if($resArr) {
				$this->printList($resArr, $previewer);
			}
			else printMLText("no_docs_to_look_at");
		}
	} /* }}} */


	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$orderby = $this->params['orderby'];
		$orderdir = $this->params['orderdir'];
		$listtype = $this->params['listtype'];
		$cachedir = $this->params['cachedir'];
		$workflowmode = $this->params['workflowmode'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$db = $dms->getDB();
		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		$previewer->setConverters($previewconverters);

		$this->htmlAddHeader('<script type="text/javascript" src="../styles/'.$this->theme.'/bootbox/bootbox.min.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("my_documents"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("my_documents"), "my_documents");

		echo '<div class="row-fluid">';
		echo '<div class="span3">';
		echo '<ul class="nav nav-list bs-docs-sidenav _affix">';

		echo '</ul>';
		echo '</div>';
		echo '<div class="span9">';

		echo '<div id="kkkk" class="ajax" data-view="MyDocuments" data-action="'.'listDocsToLookAt'.'"></div>';

		echo '</div>';
		echo '</div>';

		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
