<?php
require_once($Campsite['HTML_DIR'] . "/$ADMIN_DIR/languages.php");
require_once($GLOBALS['g_campsiteDir'].'/classes/Language.php');

$translator = \Zend_Registry::get('container')->getService('translator');
$languages = Language::GetLanguages(null, null, null, array(), array(), true);
$crumbs = array();
$crumbs[] = array($translator->trans("Configure"), "");
$crumbs[] = array($translator->trans("Languages"), "");
$breadcrumbs = camp_html_breadcrumbs($crumbs);
echo $breadcrumbs;

if ($g_user->hasPermission('ManageLanguages')) { ?>
<P>
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" class="action_buttons">
<TR>
    <TD><A HREF="/<?php echo $ADMIN; ?>/languages/add_modify.php"><IMG SRC="<?php echo $Campsite["ADMIN_IMAGE_BASE_URL"]; ?>/add.png" BORDER="0"></A></TD>
    <TD><A HREF="/<?php echo $ADMIN; ?>/languages/add_modify.php"><B><?php echo $translator->trans("Add new language", array(), 'languages'); ?></B></A></TD>
</TR>
</TABLE>
<?php  } ?>

<?php camp_html_display_msgs(); ?>

<P>
<?php
if (count($languages) > 0) {
	$color= 0; ?>
	<TABLE BORDER="0" CELLSPACING="1" CELLPADDING="3" class="table_list">
	<TR class="table_list_header">
		<TD ALIGN="LEFT" VALIGN="TOP"><?php  echo $translator->trans("Language"); ?></TD>
		<TD ALIGN="LEFT" VALIGN="TOP"><?php  echo $translator->trans("Native name", array(), 'languages'); ?></TD>
		<TD ALIGN="LEFT" VALIGN="TOP"><?php  echo $translator->trans("Code"); ?></TD>
        <?php if ($g_user->hasPermission('DeleteLanguages')) { ?>
		<TD ALIGN="LEFT" VALIGN="TOP"><B><?php  echo $translator->trans("Delete"); ?></B></TD>
        <?php  } ?>
	</TR>
    <?php
    foreach ($languages as $language) { ?>
	<TR <?php  if ($color) { $color=0; ?>class="list_row_even"<?php  } else { $color=1; ?>class="list_row_odd"<?php  } ?>>
		<TD>
			<?php if ($g_user->hasPermission('ManageLanguages')) { ?>
			<A HREF="/<?php echo $ADMIN; ?>/languages/add_modify.php?f_language_id=<?php p($language->getLanguageId()); ?>">
			<?php } ?>
			<?php  p(htmlspecialchars($language->getName())); ?>
			<?php if ($g_user->hasPermission('ManageLanguages')) { ?>
			</a>
			<?php } ?>
		</TD>
		<TD>
			<?php p(htmlspecialchars($language->getNativeName())); ?>
		</TD>
		<TD>
			<?php p(htmlspecialchars($language->getCode())); ?>&nbsp;
		</TD>

	<?php
	if ($g_user->hasPermission('DeleteLanguages')) { ?>
	<TD ALIGN="CENTER">
		<A HREF="/<?php echo $ADMIN; ?>/languages/do_del.php?Language=<?php p($language->getLanguageId()); ?>&amp;<?php echo SecurityToken::URLParameter(); ?>" onclick="return confirm('<?php echo $translator->trans('Are you sure you want to delete the language $1?', array('$1' => $language->getNativeName()), 'languages');?>');"><IMG SRC="<?php echo $Campsite["ADMIN_IMAGE_BASE_URL"]; ?>/delete.png" BORDER="0" ALT="<?php  echo $translator->trans('Delete language $1', array('$1' => htmlspecialchars($language->getNativeName())), 'languages'); ?>" TITLE="<?php  echo $translator->trans('Delete language $1', array('$1' => htmlspecialchars($language->getNativeName())), 'languages'); ?>"></A>
		</TD>
	<?php  } ?>
	</TR>
    <?php
    } // foreach
    ?>
    </TABLE>
    <?php
} else { ?>
    <BLOCKQUOTE>
	<LI><?php  echo $translator->trans('No language.', array(), 'languages'); ?></LI>
    </BLOCKQUOTE>
<?php  } ?>
<?php camp_html_copyright_notice(); ?>