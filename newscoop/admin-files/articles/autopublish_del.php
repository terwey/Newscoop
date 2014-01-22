<?PHP
require_once($GLOBALS['g_campsiteDir']. "/$ADMIN_DIR/articles/article_common.php");
require_once($GLOBALS['g_campsiteDir'].'/classes/ArticlePublish.php');

$translator = \Zend_Registry::get('container')->getService('translator');

if (!SecurityToken::isValid()) {
    camp_html_display_error($translator->trans('Invalid security token!'));
    exit;
}

if (!$g_user->hasPermission("Publish")) {
	camp_html_display_error($translator->trans("You do not have the right to schedule issues or articles for automatic publishing.", array(), 'articles'));
	exit;
}

$f_language_selected = Input::Get('f_language_selected', 'int', 0);
$f_language_id = Input::Get('f_language_id', 'int', 0);
$f_article_number = Input::Get('f_article_number', 'int', 0);
$f_event_id = Input::Get('f_event_id', 'int', 0);
$BackLink = Input::Get('Back', 'string', "/$ADMIN/articles/index.php", true);

if (!Input::IsValid()) {
	camp_html_display_error($translator->trans('Invalid input: $1', array('$1' => Input::GetErrorString())), $BackLink);
	exit;
}

$articleObj = new Article($f_language_selected, $f_article_number);
if (!$articleObj->exists()) {
	camp_html_display_error($translator->trans('Article does not exist.'), $BackLink);
	exit;
}

$articlePublishObj = new ArticlePublish($f_event_id);
if ($articlePublishObj->exists()) {
	$articlePublishObj->delete();
}
camp_html_add_msg($translator->trans("Scheduled action deleted.", array(), 'articles'), "ok");
$redirect = camp_html_article_url($articleObj, $f_language_id, "edit.php");
camp_html_goto_page($redirect);
?>