<?php
require_once($GLOBALS['g_campsiteDir'].'/classes/Article.php');
require_once($GLOBALS['g_campsiteDir'].'/classes/Attachment.php');
require_once($GLOBALS['g_campsiteDir'].'/classes/ArticleAttachment.php');
require_once($GLOBALS['g_campsiteDir'].'/classes/Translation.php');
require_once($GLOBALS['g_campsiteDir'].'/classes/Input.php');

$translator = \Zend_Registry::get('container')->getService('translator');

/**
 * Set message
 * @param string $message
 * @return void
 */
function setMessage($message, $isError = FALSE)
{
    if (empty($_REQUEST['archive'])) { // fancybox
        echo '<script type="text/javascript">';
        echo 'try {';

        if (!$isError) {
            echo 'parent.$.fancybox.reload = true;';
            echo 'parent.$.fancybox.message = "', $message, '";';
        } else {
            echo 'parent.$.fancybox.error = "', $message, '";';
        }

        echo 'parent.$.fancybox.close();';
        echo '} catch (e) {}';
        echo '</script>';
        exit;
    }

    if ($isError) {
	    camp_html_display_error($message, null, true);
        exit;
    }

    camp_html_add_msg($message);
}

if (empty($_POST)) {
    setMessage($translator->trans('The file exceeds the allowed max file size.', array(), 'article_files'), TRUE);
}

if (!SecurityToken::isValid()) {
    setMessage(SecurityToken::GetToken(), TRUE);
    setMessage($translator->trans('Invalid security token!'), TRUE);
}

if (!$g_user->hasPermission('AddFile')) {
    setMessage($translator->trans('You do not have the right to add files.', array(), 'article_files'), TRUE);
}

// We set to unlimit the maximum time to execution whether
// safe_mode is disabled. Upload is still under control of
// max upload size.
if (!ini_get('safe_mode')) {
	set_time_limit(0);
}

$inArchive = !empty($_REQUEST['archive']);

if (!$inArchive) {
    $f_language_id = Input::Get('f_language_id', 'int', 0);
    $f_language_selected = Input::Get('f_language_selected', 'int', 0);
    $f_article_number = Input::Get('f_article_number', 'int', 0);

    $articleObj = new Article($f_language_selected, $f_article_number);
    if (!$articleObj->exists()) {
        setMessage($translator->trans("Article does not exist."), TRUE);
    }
}

$f_description = Input::Get('f_description');
$f_language_specific = Input::Get('f_language_specific');
$f_content_disposition = Input::Get('f_content_disposition');

$BackLink = Input::Get('BackLink', 'string', null, true);

if (isset($_FILES["f_file"])) {
	switch($_FILES["f_file"]['error']) {
		case 0: // UPLOAD_ERR_OK
			break;

		case 1: // UPLOAD_ERR_INI_SIZE
		case 2: // UPLOAD_ERR_FORM_SIZE
            setMessage($translator->trans("The file exceeds the allowed max file size.", array(), 'article_files'), TRUE);
			break;

		case 3: // UPLOAD_ERR_PARTIAL
			setMessage($translator->trans("The uploaded file was only partially uploaded. This is common when the maximum time to upload a file is low in contrast with the file size you are trying to input. The maximum input time is specified in 'php.ini'", array(), 'article_files'), TRUE);
			break;

		case 4: // UPLOAD_ERR_NO_FILE
			setMessage($translator->trans("You must select a file to upload.", array(), 'article_files'), TRUE);
			break;

		case 6: // UPLOAD_ERR_NO_TMP_DIR
		case 7: // UPLOAD_ERR_CANT_WRITE
			setMessage($translator->trans("There was a problem uploading the file.", array(), 'article_files'), TRUE);
			break;
    }
} else {
	setMessage($translator->trans("The file exceeds the allowed max file size.", array(), 'article_files'), TRUE);
}

if (!Input::IsValid()) {
	setMessage($translator->trans('Invalid input: $1', array('$1' => Input::GetErrorString())), TRUE);
}

$attributes = array();
$attributes['fk_user_id'] = $g_user->getUserId();
if ($f_language_specific == "yes") {
	$attributes['fk_language_id'] = $f_language_selected;
} else {
    $description = new Translation(0);
    $description->create($f_description);
    $attributes['fk_description_id'] = $description->getPhraseId();
}
if ($f_content_disposition == "attachment") {
	$attributes['content_disposition'] = "attachment";
}

if (!empty($_FILES['f_file'])) {
	$file = Attachment::OnFileUpload($_FILES['f_file'], $attributes);
} else {
	camp_html_goto_page(camp_html_article_url($articleObj, $f_language_id, 'files/popup.php'));
}

// Check if image was added successfully
if (PEAR::isError($file)) {
    setMessage($file->getMessage());
	camp_html_goto_page($BackLink);
}

if (!$inArchive) {
    ArticleAttachment::AddFileToArticle($file->getAttachmentId(), $articleObj->getArticleNumber());

    $logtext = $translator->trans('File #$1 "$2" attached to article',
        array('$1' => $file->getAttachmentId(), '$2' => $file->getFileName()), 'article_files');
    Log::ArticleMessage($articleObj, $logtext, null, 38, TRUE);

    setMessage($translator->trans('File attached.', array(), 'article_files'));
} else { ?>
<script type="text/javascript"><!--
    if (opener && !opener.closed && opener.onUpload) {
        opener.onUpload();
        opener.focus();
        window.close();
    }
//--></script>
<?php } ?>
