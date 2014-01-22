<?php
require_once($GLOBALS['g_campsiteDir']. "/$ADMIN_DIR/articles/article_common.php");

$translator = \Zend_Registry::get('container')->getService('translator');

if (!SecurityToken::isValid()) {
    camp_html_display_error($translator->trans('Invalid security token!'));
    exit;
}

// Get input
$f_publication_id = Input::Get('f_publication_id', 'int', 0);
$f_issue_number = Input::Get('f_issue_number', 'int', 0);
$f_section_number = Input::Get('f_section_number', 'int', 0);
$f_language_id = Input::Get('f_language_id', 'int', 0);
$f_language_selected = Input::Get('f_language_selected', 'int', 0);
$f_article_codes = Input::Get('f_article_code', 'array', array(), true);
$f_article_list_action = Input::Get('f_article_list_action');
$f_total_articles = Input::Get('f_total_articles', 'int', 0);
$offsetVarName = "f_article_offset_".$f_publication_id."_".$f_issue_number."_".$f_language_id."_".$f_section_number;
$f_article_offset = camp_session_get($offsetVarName, 0);
$ArticlesPerPage = 15;

if (sizeof($f_article_codes) == 0) {
	camp_html_add_msg('You must select at least one article to perform an action.');
	camp_html_goto_page("/$ADMIN/articles/?f_publication_id=$f_publication_id&f_issue_number=$f_issue_number"
				."&f_section_number=$f_section_number&f_language_id=$f_language_id");
	exit(0);
}

if (!Input::IsValid()) {
	camp_html_display_error($translator->trans('Invalid input: $1', array('$1' => Input::GetErrorString())));
	exit;
}

if ($f_article_offset < 0) {
	$f_article_offset = 0;
}

// Validate permissions
switch ($f_article_list_action) {
case "delete":
	if (!$g_user->hasPermission('DeleteArticle')) {
		camp_html_display_error($translator->trans("You do not have the right to delete articles.", array(), 'articles'));
		exit;
	}
	break;
case "publish":
	if (!$g_user->hasPermission('Publish')) {
		$errorStr = $translator->trans("You do not have the right to change this article status. Once submitted an article can only be changed by authorized users.", array(), 'articles');
		camp_html_display_error($errorStr, $BackLink);
		exit;
	}
	break;
case "copy":
case "copy_interactive":
	if (!$g_user->hasPermission('AddArticle')) {
		$errorStr = $translator->trans("You do not have the right to add articles.");
		camp_html_display_error($errorStr, $BackLink);
		exit;
	}
	break;
}


$articleCodes = array();
$groupedArticleCodes = array();
foreach ($f_article_codes as $code) {
	list($articleId, $languageId) = explode("_", $code);
	$articleCodes[] = array("article_id" => $articleId, "language_id" => $languageId);
	$groupedArticleCodes[$articleId][$languageId] = $languageId;
}

switch ($f_article_list_action) {
case "workflow_new":
	foreach ($articleCodes as $articleCode) {
		$articleObj = new Article($articleCode['language_id'], $articleCode['article_id']);
		// A publisher can change the status in any way he sees fit.
		// Someone who can change an article can submit/unsubmit articles.
		if ($g_user->hasPermission('Publish')
			|| ($g_user->hasPermission('ChangeArticle') && ($articleObj->getWorkflowStatus() == 'S'))) {
			$articleObj->setWorkflowStatus('N');
		}
	}
	camp_html_add_msg($translator->trans("Article status set to $1", array('$1' => $translator->trans("New")), 'articles'), "ok");
	break;
case "workflow_submit":
	foreach ($articleCodes as $articleCode) {
		$articleObj = new Article($articleCode['language_id'], $articleCode['article_id']);
		// A user who owns the article may submit it.
		if ($g_user->hasPermission("Publish") || $articleObj->userCanModify($g_user)) {
			$articleObj->setWorkflowStatus('S');
		}
	}
	camp_html_add_msg($translator->trans("Article status set to $1", array('$1' => $translator->trans("Submitted")), 'articles'), "ok");
	break;
case "workflow_publish":
	foreach ($articleCodes as $articleCode) {
		$articleObj = new Article($articleCode['language_id'], $articleCode['article_id']);
		$articleObj->setWorkflowStatus('Y');

        \Zend_Registry::get('container')->getService('dispatcher')
            ->dispatch('article.publish', new \Newscoop\EventDispatcher\Events\GenericEvent($this, array(
                'article' => $articleObj
            )));
	}
	camp_html_add_msg($translator->trans("Article status set to $1", array('$1' => $translator->trans("Published")), 'articles'), "ok");
	break;
case "delete":
	foreach ($articleCodes as $articleCode) {
		$articleObj = new Article($articleCode['language_id'], $articleCode['article_id']);
		$articleObj->delete();
	}
	if ($f_article_offset > 15
	    && (count($articleCodes) + $f_article_offset) == $f_total_articles) {
		$f_article_offset -= $ArticlesPerPage;
	}
	camp_html_add_msg($translator->trans("Article(s) deleted.", array(), 'articles'), "ok");
	break;
case "toggle_front_page":
	foreach ($articleCodes as $articleCode) {
		$articleObj = new Article($articleCode['language_id'], $articleCode['article_id']);
		if ($articleObj->userCanModify($g_user)) {
			$articleObj->setOnFrontPage(!$articleObj->onFrontPage());
		}
	}
	camp_html_add_msg($translator->trans("$1 toggled.", array('$1' => "&quot;".$translator->trans("On Front Page")."&quot;"), 'articles'), "ok");
	break;
case "toggle_section_page":
	foreach ($articleCodes as $articleCode) {
		$articleObj = new Article($articleCode['language_id'], $articleCode['article_id']);
		if ($articleObj->userCanModify($g_user)) {
			$articleObj->setOnSectionPage(!$articleObj->onSectionPage());
		}
	}
	camp_html_add_msg($translator->trans("$1 toggled.", array('$1' => "&quot;".$translator->trans("On Section Page")."&quot;"), 'articles'), "ok");
	break;
case "toggle_comments":
	foreach ($articleCodes as $articleCode) {
		$articleObj = new Article($articleCode['language_id'], $articleCode['article_id']);
		if ($articleObj->userCanModify($g_user)) {
			$articleObj->setCommentsEnabled(!$articleObj->commentsEnabled());
		}
	}
	camp_html_add_msg($translator->trans("$1 toggled.", array('$1' => "&quot;".$translator->trans("Comments")."&quot;"), 'articles'), "ok");
	break;
case "copy":
	foreach ($groupedArticleCodes as $articleNumber => $languageArray) {
		$languageId = camp_array_peek($languageArray);
		$articleObj = new Article($languageId, $articleNumber);
		$articleObj->copy($articleObj->getPublicationId(),
						  $articleObj->getIssueNumber(),
						  $articleObj->getSectionNumber(),
						  $g_user->getUserId(),
						  $languageArray);

        \Zend_Registry::get('container')->getService('dispatcher')
            ->dispatch('article.duplicate', new \Newscoop\EventDispatcher\Events\GenericEvent($this, array(
                'article' => $articleObj,
                'orginal_article_number' => $articleNumber
            )));

		camp_html_add_msg($translator->trans("Article(s) duplicated.", array(), 'articles'), "ok");
	}
	camp_session_set($offsetVarName, 0);
	break;
case "copy_interactive":
	$args = $_REQUEST;
	unset($args[SecurityToken::SECURITY_TOKEN]);
	unset($args["f_article_code"]);
	$argsStr = camp_implode_keys_and_values($args, "=", "&");
	$argsStr .= "&f_mode=multi&f_action=duplicate";
	foreach ($_REQUEST["f_article_code"] as $code) {
		$argsStr .= "&f_article_code[]=$code";
	}
	camp_session_set($offsetVarName, 0);
	camp_html_goto_page("/$ADMIN/articles/duplicate.php?".$argsStr);
case "move":
	$args = $_REQUEST;
	unset($args[SecurityToken::SECURITY_TOKEN]);
	unset($args["f_article_code"]);
	$argsStr = camp_implode_keys_and_values($args, "=", "&");
	$argsStr .= "&f_mode=multi&f_action=move";
	foreach ($_REQUEST["f_article_code"] as $code) {
		$argsStr .= "&f_article_code[]=$code";
	}
	camp_session_set($offsetVarName, 0);
	camp_html_goto_page("/$ADMIN/articles/duplicate.php?".$argsStr);
case "unlock":
	foreach ($articleCodes as $articleCode) {
		$articleObj = new Article($articleCode['language_id'], $articleCode['article_id']);
		if ($articleObj->userCanModify($g_user)) {
			$articleObj->setIsLocked(false);
		}
	}
	camp_html_add_msg($translator->trans("Article(s) unlocked.", array(), 'articles'), "ok");
	break;
case "context_box_update":
	camp_html_add_msg($translator->trans("Context Box updated", array(), 'articles'), "ok");
	break;
case "schedule_publish":
	$args = $_REQUEST;
	unset($args[SecurityToken::SECURITY_TOKEN]);
	unset($args["f_article_code"]);
	$argsStr = camp_implode_keys_and_values($args, "=", "&");
	foreach ($_REQUEST["f_article_code"] as $code) {
		$argsStr .= "&f_article_code[]=$code";
	}
	camp_html_goto_page("/$ADMIN/articles/multi_autopublish.php?".$argsStr);
case "translate":
	$args = $_REQUEST;
	unset($args[SecurityToken::SECURITY_TOKEN]);
	unset($args["f_article_code"]);
	$argsStr = camp_implode_keys_and_values($args, "=", "&");
	foreach ($_REQUEST["f_article_code"] as $code) {
		$argsStr .= "&f_article_code=$code";
		break;
	}
	camp_html_goto_page("/$ADMIN/articles/translate.php?".$argsStr);
}

$backUrl = "/$ADMIN/articles/index.php?f_publication_id=$f_publication_id"
	.  "&f_issue_number=$f_issue_number&f_section_number=$f_section_number"
	.  "&f_language_id=$f_language_id&f_language_selected=$f_language_selected"
	.  "&$offsetVarName=$f_article_offset";
camp_html_goto_page($backUrl);
?>
