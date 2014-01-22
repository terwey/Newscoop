<?php
require_once($GLOBALS['g_campsiteDir']."/$ADMIN_DIR/issues/issue_common.php");
require_once($GLOBALS['g_campsiteDir']. '/classes/Article.php');

$translator = \Zend_Registry::get('container')->getService('translator');
// Check permissions
if (!$g_user->hasPermission('DeleteIssue')) {
	camp_html_display_error($translator->trans('You do not have the right to delete issues.', array(), 'issues'));
	exit;
}
$f_publication_id = Input::Get('f_publication_id', 'int');
$f_issue_number = Input::Get('f_issue_number', 'int');
$f_language_id = Input::Get('f_language_id', 'int');

if (!Input::IsValid()) {
	camp_html_display_error($translator->trans('Invalid Input: $1', array('$1' => Input::GetErrorString()), 'issues'));
	exit;
}

$numArticles = count(Article::GetArticles($f_publication_id, $f_issue_number, null, $f_language_id));
$publicationObj = new Publication($f_publication_id);
$issueObj = new Issue($f_publication_id, $f_language_id, $f_issue_number);

camp_html_content_top($translator->trans('Delete issue', array(), 'issues'), array('Pub' => $publicationObj, 'Issue' => $issueObj));

?>
<P>
<FORM METHOD="POST" ACTION="/<?php echo $ADMIN; ?>/issues/do_del.php">
<?php echo SecurityToken::FormParameter(); ?>
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="8" class="message_box">
<TR>
	<TD COLSPAN="2">
		<B> <?php echo $translator->trans("Delete issue", array(), 'issues'); ?> </B>
		<HR NOSHADE SIZE="1" COLOR="BLACK">
	</TD>
</TR>
<TR>
	<TD COLSPAN="2" align="center">
		<?php echo $translator->trans('There are $1 articles in this issue.', array('$1' => '<b>'.$numArticles.'</b>'), 'issues'); ?>
	</TD>
</TR>
<TR>
	<TD COLSPAN="2" align="center"><?php echo $translator->trans('Are you sure you want to delete the issue $1?', array('$1' => '<B>'.htmlspecialchars($issueObj->getName()).' ('.$issueObj->getLanguageName().')</B>'), 'issues'); ?></TD>
</TR>
<TR>
	<TD COLSPAN="2" align="center">
	<INPUT TYPE="HIDDEN" NAME="f_publication_id" VALUE="<?php p($f_publication_id); ?>">
	<INPUT TYPE="HIDDEN" NAME="f_issue_number" VALUE="<?php p($f_issue_number); ?>">
	<INPUT TYPE="HIDDEN" NAME="f_language_id" VALUE="<?php p($f_language_id); ?>">
	<INPUT TYPE="submit" class="button" NAME="Yes" VALUE="<?php echo $translator->trans('Yes'); ?>">
	&nbsp;&nbsp;&nbsp;&nbsp;
	<INPUT TYPE="button" class="button" NAME="No" VALUE="<?php echo $translator->trans('No'); ?>" ONCLICK="location.href='/<?php p($ADMIN);?>/issues/?Pub=<?php p($f_publication_id); ?>'">
	</TD>
</TR>
</TABLE>
    </FORM>
<P>

<?php camp_html_copyright_notice(); ?>
