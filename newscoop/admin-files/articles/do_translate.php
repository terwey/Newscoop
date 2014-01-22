<?php
require_once($GLOBALS['g_campsiteDir']."/$ADMIN_DIR/articles/article_common.php");
require_once($GLOBALS['g_campsiteDir'].'/classes/Log.php');

$translator = \Zend_Registry::get('container')->getService('translator');

if (!SecurityToken::isValid()) {
    camp_html_display_error($translator->trans('Invalid security token!'));
    exit;
}

if (!$g_user->hasPermission("AddArticle")) {
    $errorStr = $translator->trans('You do not have the right to change this article.  You may only edit your own articles and once submitted an article can only be changed by authorized users.', array(), 'articles');
    camp_html_display_error($errorStr);
    exit;
}

// Optional input.
$f_language_id = Input::Get('f_language_id', 'int', 0, true);
$f_publication_id = Input::Get('f_publication_id', 'int', 0, true);
$f_issue_number = Input::Get('f_issue_number', 'int', 0, true);
$f_section_number = Input::Get('f_section_number', 'int', 0, true);

// Required input.
$f_article_code = Input::Get('f_article_code', 'string', 0);
$f_translation_title = trim(Input::Get('f_translation_title'));
$f_translation_language = Input::Get('f_translation_language');
list($articleNumber, $languageId) = explode("_", $f_article_code);
$backLink = "/$ADMIN/articles/translate.php?f_language_id=$f_language_id"
        . "&f_publication_id=$f_publication_id&f_issue_number=$f_issue_number"
        . "&f_section_number=$f_section_number&f_article_code=$f_article_code"
        . "&f_translation_title=$f_translation_title&f_translation_language=$f_translation_language";

if (!Input::IsValid()) {
    camp_html_display_error($translator->trans('Invalid input: $1', array('$1' => Input::GetErrorString())));
    exit;
}

$articleObj = new Article($languageId, $articleNumber);
if (!$articleObj->exists()) {
    camp_html_display_error($translator->trans('Article does not exist.'));
    exit;
}

$translationLanguageObj = new Language($f_translation_language);
if (!$translationLanguageObj->exists()) {
    camp_html_display_error($translator->trans('Language does not exist.'));
    exit;
}

$translationArticle = new Article($f_translation_language, $articleNumber);
if ($translationArticle->exists()) {
    camp_html_add_msg($translator->trans("The article has already been translated into $1.", array('$1' => $translationLanguageObj->getNativeName()), 'articles'));
    camp_html_goto_page($backLink);
}

$f_publication_id = $articleObj->getPublicationId();

// Only create the translated issue and section if the article has been categorized.
if ($f_publication_id > 0) {
    $publicationObj = new Publication($f_publication_id);
    if (!$publicationObj->exists()) {
        camp_html_display_error($translator->trans('Publication does not exist.'), $backLink);
        exit;
    }

    $f_issue_number = $articleObj->getIssueNumber();
    $issueObj = new Issue($f_publication_id, $f_language_id, $f_issue_number);
    if (!$issueObj->exists()) {
        camp_html_display_error($translator->trans('No such issue.'), $backLink);
        exit;
    }

    $translationIssueObj = $issueObj->copy(null, $issueObj->getIssueNumber(), $f_translation_language);
    if (!$translationIssueObj) {
        $translationIssueObj = new Issue($f_publication_id, $f_translation_language, $f_issue_number);
    }
    
    if (!$translationIssueObj->exists()) {
        if (!$g_user->hasPermission("ManageIssue")) {
            camp_html_add_msg($translator->trans('An issue must be created for the selected language but you do not have the right to create an issue.', array(), 'articles'));
            camp_html_goto_page($backLink);
        }

        foreach ($issueObj->getData() as $field=>$fieldValue) {
            if ($field != 'IdLanguage') {
                $translationIssueObj->setProperty($field, $fieldValue, false);
            }
        }

        $f_issue_name = Input::Get('f_issue_name', 'string', '');
        $f_issue_urlname = Input::Get('f_issue_urlname', 'string', $issueObj->getUrlName());
        
        if ($f_issue_name != '') {
            $translationIssueObj->setName($f_issue_name);
        }
        
        if ($f_issue_urlname == "") {
            camp_html_add_msg($translator->trans('You must fill in the $1 field.', array('$1' => '"'.$translator->trans('New issue URL name', array(), 'articles').'"')));
            camp_html_goto_page($backLink);
        }

        if (!camp_is_valid_url_name($f_issue_urlname)) {
            camp_html_add_msg($translator->trans('The $1 field may only contain letters, digits and underscore (_) character.', array('$1' => '"' . $translator->trans('New issue URL name', array(), 'articles') . '"')));
            camp_html_goto_page($backLink);
        }

        $translationIssueObj->create($f_issue_urlname);
        if (!$translationIssueObj->exists()) {
            camp_html_add_msg($translator->trans('Unable to create the issue for translation $1.', array('$1' => $translationLanguageObj->getName()), 'articles'));
            camp_html_goto_page($backLink);
        }
    } else {
        $f_issue_name = Input::Get('f_issue_name', 'string', $translationIssueObj->getName());
        $f_issue_urlname = Input::Get('f_issue_urlname', 'string', $translationIssueObj->getUrlName());
        
        $translationIssueObj->update(array('Name' => $f_issue_name, 'ShortName' => $f_issue_urlname));
    }

    $f_section_number = $articleObj->getSectionNumber();
    $sectionObj = new Section($f_publication_id, $f_issue_number, $f_language_id, $f_section_number);
    if (!$sectionObj->exists()) {
        camp_html_display_error($translator->trans('No such section.', array(), 'articles'), $backLink);
        exit;
    }

    $translationSectionObj = new Section($f_publication_id, $f_issue_number, $f_translation_language, $f_section_number);
    if (!$translationSectionObj->exists()) {
        if (!$g_user->hasPermission("ManageSection")) {
            camp_html_add_msg($translator->trans('A section must be created for the selected language but you do not have the right to create a section.', array(), 'articles'));
            camp_html_goto_page($backLink);
        }

        foreach ($sectionObj->getData() as $field=>$fieldValue) {
            if (!in_array($field, array('IdLanguage', 'id'))) {
                $translationSectionObj->setProperty($field, $fieldValue, false);
            }
        }

        $f_section_name = Input::Get('f_section_name', 'string', $sectionObj->getName());
        $f_section_urlname = Input::Get('f_section_urlname', 'string', $sectionObj->getUrlName());
        if ($f_section_urlname == "") {
            camp_html_add_msg($translator->trans('You must fill in the $1 field.', array('$1' => '"'.$translator->trans('New section URL name', array(), 'articles').'"')));
            camp_html_goto_page($backLink);
        }

        if (!camp_is_valid_url_name($f_section_urlname)) {
            camp_html_add_msg($translator->trans('The $1 field may only contain letters, digits and underscore (_) character.', array('$1' => '"' . $translator->trans('New section URL name', array(), 'articles') . '"')));
            camp_html_goto_page($backLink);
        }

        $translationSectionObj->create($f_section_name, $f_section_urlname);
        if (!$translationSectionObj->exists()) {
            camp_html_add_msg($translator->trans('Unable to create the section for translation $1.', array('$1' => $translationLanguageObj->getName()), 'articles'));
            camp_html_goto_page($backLink);
        }
    } else {
        $f_section_name = Input::Get('f_section_name', 'string', $translationSectionObj->getName());
        $f_section_urlname = Input::Get('f_section_urlname', 'string', $translationSectionObj->getUrlName());
        
        $translationSectionObj->update(array('Name' => $f_section_name, 'ShortName' => $f_section_urlname));
    }
}

if($articleObj->translationTitleExists($f_translation_title, $f_translation_language)) {
    camp_html_add_msg($translator->trans("There already is an article with the same name. Please change the name and try again.", array(), 'articles'), "ok");
    camp_html_goto_page($backLink);
    exit();
}

$articleCopy = $articleObj->createTranslation($f_translation_language, $g_user->getUserId(), $f_translation_title);

\Zend_Registry::get('container')->getService('dispatcher')
    ->dispatch('article.translate', new \Newscoop\EventDispatcher\Events\GenericEvent($this, array(
        'article' => $articleCopy
    )));

camp_html_add_msg($translator->trans("Article translation created.", array(), 'articles'), "ok");

ArticleIndex::RunIndexer(3, 10, true);
camp_html_goto_page(camp_html_article_url($articleCopy, $f_translation_language, 'edit.php'), true);
