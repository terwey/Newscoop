<?php
require_once($GLOBALS['g_campsiteDir']."/$ADMIN_DIR/articles/article_common.php");
require_once($GLOBALS['g_campsiteDir'].'/classes/Image.php');

$translator = \Zend_Registry::get('container')->getService('translator');
$f_backlink = Input::Get('Back', 'string', '');
$f_image_id = Input::Get('f_image_id', 'int', 0);

if (!Input::IsValid()) {
	camp_html_display_error($translator->trans('Invalid input: $1', array('$1' => Input::GetErrorString())), $_SERVER['REQUEST_URI'], true);
	exit;
}

$imageObj = new Image($f_image_id);

// Add extra breadcrumb for image list.
?>
<P>
<div class="indent">
<a href="<?php p($f_backlink); ?>"><?php echo $translator->trans("Back"); ?>
<p>
<IMG SRC="<?php echo $imageObj->getImageUrl(); ?>" BORDER="0" ALT="<?php echo htmlspecialchars($imageObj->getDescription()); ?>">
</a>
</div>
<p>
