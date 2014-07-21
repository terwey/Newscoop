<?php
use Newscoop\Service\ISyncResourceService;
use Newscoop\Service\IIssueService;
use Newscoop\Service\IOutputService;
require_once($GLOBALS['g_campsiteDir']."/$ADMIN_DIR/issues/issue_common.php");
//@New theme management
use Newscoop\Service\Resource\ResourceId;
use Newscoop\Service\IThemeManagementService;
use Newscoop\Service\IOutputSettingIssueService;
use Newscoop\Entity\Output\OutputSettingsIssue;
//@New theme management

$translator = \Zend_Registry::get('container')->getService('translator');

if (!SecurityToken::isValid()) {
    camp_html_display_error($translator->trans('Invalid security token!'));
    exit;
}

// Check permissions
if (!$g_user->hasPermission('ManageIssue')) {
	camp_html_display_error($translator->trans('You do not have the right to change issue details.', array(), 'issues'));
	exit;
}

$f_publication_id = Input::Get('f_publication_id', 'int');
$f_issue_number = Input::Get('f_issue_number', 'int');
$f_current_language_id = Input::Get('f_current_language_id', 'int');
$f_issue_name = trim(Input::Get('f_issue_name'));
$f_new_language_id = Input::Get('f_new_language_id', 'int');
$f_publication_date = Input::Get('f_publication_date', 'string', '', true);


$f_theme_id = Input::Get('f_theme_id', 'string');
$f_issue_template_id = Input::Get('f_issue_template_id', 'string');
$f_section_template_id = Input::Get('f_section_template_id', 'string');
$f_article_template_id = Input::Get('f_article_template_id', 'string');


$f_url_name = trim(Input::Get('f_url_name'));

if (!Input::IsValid()) {
	camp_html_display_error($translator->trans('Invalid input: $1', array('$1' => Input::GetErrorString()), 'issues'));
	exit;
}
$publicationObj = new Publication($f_publication_id);
$issueObj = new Issue($f_publication_id, $f_current_language_id, $f_issue_number);

$backLink = "/$ADMIN/issues/edit.php?Pub=$f_publication_id&Issue=$f_issue_number&Language=$f_current_language_id";
if ($f_new_language_id == 0) {
	camp_html_add_msg($translator->trans('You must select a language.'));
}
if (empty($f_issue_name)) {
	camp_html_add_msg($translator->trans('You must fill in the $1 field.', array('$1' => "'".$translator->trans('Name')."'")));
}
if (empty($f_url_name)) {
	camp_html_add_msg($translator->trans('You must fill in the $1 field.', array('$1' => "'".$translator->trans('URL Name')."'")));
}
if (!camp_is_valid_url_name($f_url_name)) {
	camp_html_add_msg($translator->trans('The $1 field may only contain letters, digits and underscore (_) character.', array('$1' => "'" . $translator->trans('URL Name') . "'")));
}
if (camp_html_has_msgs()) {
	camp_html_goto_page($backLink);
}

$changed = true;
$changed &= $issueObj->setName($f_issue_name);
if ($issueObj->getWorkflowStatus() == 'Y') {
	$changed &= $issueObj->setPublicationDate($f_publication_date);
}

//@New theme management
$resourceId = new ResourceId('Publication/Edit');
$themeManagementService = $resourceId->getService(IThemeManagementService::NAME_1);
$outputSettingIssueService = $resourceId->getService(IOutputSettingIssueService::NAME);
$outputService = $resourceId->getService(IOutputService::NAME);
$issueService = $resourceId->getService(IIssueService::NAME);
$syncRsc = $resourceId->getService(ISyncResourceService::NAME);

$newOutputSetting = false;

$outSetIssues = $outputSettingIssueService->findByIssue($issueObj->getIssueId());
if(count($outSetIssues) > 0){
	$outSetIssue = $outSetIssues[0];
} else {
	$outSetIssue = new OutputSettingsIssue();
	$outSetIssue->setOutput($outputService->findByName('Web'));
	$outSetIssue->setIssue($issueService->getById($issueObj->getIssueId()));
	$newOutputSetting = true;
}
$outSetIssue->setThemePath($syncRsc->getThemePath($f_theme_id));
if($f_issue_template_id != null && $f_issue_template_id != '0'){
	$outSetIssue->setFrontPage($syncRsc->getResource('frontPage', $f_issue_template_id));
} else {
	$outSetIssue->setFrontPage(null);
}
if($f_section_template_id != null && $f_section_template_id != '0'){
	$outSetIssue->setSectionPage($syncRsc->getResource('sectionPage', $f_section_template_id));
} else {
	$outSetIssue->setSectionPage(null);
}
if($f_article_template_id != null && $f_article_template_id != '0'){
	$outSetIssue->setArticlePage($syncRsc->getResource('articlePage', $f_article_template_id));
} else {
	$outSetIssue->setArticlePage(null);
}
//@New theme management

if ($changed) {
        
} else {
	$errMsg = $translator->trans("Could not save the changes to the issue.", array(), 'issues');
	camp_html_add_msg($errMsg);
	exit;
}

// The tricky part - language ID and URL name must be unique.
$conflictingIssues = Issue::GetIssues($f_publication_id, $f_new_language_id, null, $f_url_name, null, false, null, true);
$conflictingIssue = array_pop($conflictingIssues);
// If it conflicts with another issue
if ($errorMsg = camp_is_issue_conflicting($f_publication_id, $f_issue_number, $f_new_language_id, $f_url_name, true)) {
	camp_html_add_msg($errorMsg);
	camp_html_goto_page($backLink);
} else {
	$issueObj->setProperty('ShortName', $f_url_name, false);
	$issueObj->setProperty('IdLanguage', $f_new_language_id, false);
	$issueObj->commit();
	//@New theme management
    if(SaaS::singleton()->hasPermission('ManageIssueTemplates')) {
        if($newOutputSetting){
            $outputSettingIssueService->insert($outSetIssue);
        } else {
            $outputSettingIssueService->update($outSetIssue);
        }
    }

    $cacheService = \Zend_Registry::get('container')->getService('newscoop.cache');
    $cacheService->clearNamespace('issue');

	//@New theme management
	$link = "/$ADMIN/issues/edit.php?Pub=$f_publication_id&Issue=$f_issue_number&Language=".$issueObj->getLanguageId();
	camp_html_add_msg($translator->trans('Issue updated', array(), 'issues'), "ok");
	camp_html_goto_page($link);
}

?>
