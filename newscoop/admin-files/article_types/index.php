<?php
require_once($GLOBALS['g_campsiteDir'].'/classes/ArticleType.php');
require_once($GLOBALS['g_campsiteDir'].'/classes/Translation.php');

$translator = \Zend_Registry::get('container')->getService('translator');

if (!$g_user->hasPermission('ManageArticleTypes') && !$g_user->hasPermission('DeleteArticleTypes')) {
	camp_html_goto_page("/$ADMIN/");
}

$articleTypes = ArticleType::GetArticleTypes(true);
// return value is sorted by language
$allLanguages = Language::GetLanguages(null, null, null, array(), array(), true);

$lang = camp_session_get('LoginLanguageId', 1);
$languageObj = new Language($lang);

$crumbs = array();
$crumbs[] = array($translator->trans("Configure"), "");
$crumbs[] = array($translator->trans("Article Types"), "");

echo camp_html_breadcrumbs($crumbs);
include_once($GLOBALS['g_campsiteDir']."/$ADMIN_DIR/javascript_common.php");
?>
<script>
var type_ids = new Array;
var allShown = 0;
</script>


<?php
if (count($articleTypes))
{
	$i = 0;
	foreach ($articleTypes as $articleType)
	{ ?>

	<script>
	type_ids.push("translate_type_"+<?php p($i); ?>);
	</script>

<?php
	$i++;
	} // foreach
} // if

if ($g_user->hasPermission("ManageArticleTypes")) { ?>
	<P>
	<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="1" class="action_buttons">
	<TR>
        <TD><A HREF="/<?php echo $ADMIN; ?>/article_types/add.php?Back=<?php  print urlencode($_SERVER['REQUEST_URI']); ?>" ><IMG SRC="<?php echo $Campsite["ADMIN_IMAGE_BASE_URL"]; ?>/add.png" BORDER="0"></A></TD>
        <TD><B><A HREF="/<?php echo $ADMIN; ?>/article_types/add.php?Back=<?php  print urlencode($_SERVER['REQUEST_URI']); ?>" ><?php echo $translator->trans("Add new article type"); ?></A></B></TD>
		<TD><DIV STYLE="width:15px;"></DIV></TD>
        <TD><A HREF="/<?php echo $ADMIN; ?>/article_types/merge.php?Back=<?php  print urlencode($_SERVER['REQUEST_URI']); ?>" ><IMG SRC="<?php echo $Campsite["ADMIN_IMAGE_BASE_URL"]; ?>/merge.png" BORDER="0"></A></TD>
        <TD><B><A HREF="/<?php echo $ADMIN; ?>/article_types/merge.php?Back=<?php  print urlencode($_SERVER['REQUEST_URI']); ?>" ><?php echo $translator->trans("Merge types", array(), 'article_types'); ?></A></B></TD>
		<TD><DIV STYLE="width:15px;"></DIV></TD>
		<TD><A HREF="javascript: void(0);"
               ONCLICK="if (allShown == 0) {
                            ShowAll(type_ids);
                            allShown = 1;
                            document.getElementById('showtext').innerHTML = '<?php echo $translator->trans("Hide human-readable field names", array(), 'article_types'); ?>';
                            document['show'].src='<?php print $Campsite['ADMIN_IMAGE_BASE_URL']; ?>/viewmagminus.png';
                        } else {
                            HideAll(type_ids);
                            allShown = 0;
                            document.getElementById('showtext').innerHTML = '<?php echo $translator->trans("Edit and translate human-readable field names", array(), 'article_types'); ?>';
                            document['show'].src='<?php print $Campsite['ADMIN_IMAGE_BASE_URL']; ?>/viewmagplus.png';
                        }">
		      <IMG NAME="show" SRC="<?php echo $Campsite['ADMIN_IMAGE_BASE_URL']; ?>/viewmagplus.png" BORDER="0"></A></TD>
    	<TD><B><A HREF="javascript: void(0);"
                    ONCLICK="if (allShown == 0) {
                                ShowAll(type_ids);
                                allShown = 1;
                                document.getElementById('showtext').innerHTML = '<?php echo $translator->trans("Hide human-readable field names", array(), 'article_types'); ?>';
                                document['show'].src='<?php print $Campsite['ADMIN_IMAGE_BASE_URL']; ?>/viewmagminus.png';
                                } else {
                                HideAll(type_ids);
                                allShown = 0;
                                document.getElementById('showtext').innerHTML = '<?php echo $translator->trans("Edit and translate human-readable field names", array(), 'article_types'); ?>';
                                document['show'].src='<?php print $Campsite['ADMIN_IMAGE_BASE_URL']; ?>/viewmagplus.png';
                                }"><DIV ID="showtext"><?php echo $translator->trans("Edit and translate human-readable field names", array(), 'article_types'); ?></DIV></A></B></TD>


	</TR>
	</TABLE>

<?php  } ?>
<P>

<?php if (count($articleTypes) > 0) { ?>
<TABLE BORDER="0" CELLSPACING="1" CELLPADDING="3" class="table_list">
<TR class="table_list_header">
	<TD ALIGN="LEFT" VALIGN="TOP"><B><?php echo $translator->trans("Template Type Name", array(), 'article_types'); ?></B></TD>
	<TD ALIGN="LEFT" VALIGN="TOP"><B><?php echo $translator->trans("Fields"); ?></B></TD>
	<TD ALIGN="LEFT" VALIGN="TOP"><B><?php echo $translator->trans("Display Name", array(), 'article_types'); ?></B></TD>
	<TD ALIGN="LEFT" VALIGN="TOP"><B><?php echo $translator->trans("Translate"); ?></B></TD>
	<TD ALIGN="LEFT" VALIGN="TOP"><B><?php echo $translator->trans("Show/Hide", array(), 'article_types'); ?></B></TD>
	<TD ALIGN="LEFT" VALIGN="TOP"><B><?php echo $translator->trans("In Lists?", array(), 'article_types'); ?></B></TD>
	<TD ALIGN="LEFT" VALIGN="TOP"><B><?php echo $translator->trans("Comments enabled?", array(), 'article_types'); ?></B></TD>
	<?php  if ($g_user->hasPermission("DeleteArticleTypes")) { ?>
	<TD ALIGN="LEFT" VALIGN="TOP"><B><?php  echo $translator->trans("Delete"); ?></B></TD>
	<?php  } ?>
</TR>
<?php
$color = 0;
$i = 0;
foreach ($articleTypes as $articleType) {
	$currentArticleType = new ArticleType($articleType);
	if ($currentArticleType->getStatus() == 'hidden') {
		$hideShowText = $translator->trans('show', array(), 'article_types');
		$hideShowStatus = 'show';
		$hideShowImage = "is_hidden.png";
	} else {
		$hideShowText = $translator->trans('hide', array(), 'article_types');
		$hideShowStatus = 'hide';
		$hideShowImage = "is_shown.png";
	}

	if ($currentArticleType->commentsEnabled()) {
		$commentChangeText = $translator->trans('deactivate', array(), 'article_types');
		$commentImage = "is_shown.png";
	} else {
		$commentChangeText = $translator->trans('activate', array(), 'article_types');
		$commentImage = "is_hidden.png";
	}

    $filterChangeValue = 1;
    $filterChangeText = $translator->trans('filter', array(), 'article_types');
    $filterImage = "is_shown.png";
    if ($currentArticleType->getFilterStatus()) {
        $filterChangeValue = 0;
        $filterChangeText = $translator->trans('list', array(), 'article_types');
        $filterImage = "is_hidden.png";
    }

    ?>
    <TR <?php  if ($color) { $color=0; ?>class="list_row_even"<?php  } else { $color=1; ?>class="list_row_odd"<?php  } ?>>
	<TD>
		<A HREF="/<?php p($ADMIN); ?>/article_types/rename.php?f_name=<?php  print htmlspecialchars($articleType); ?>"><?php print htmlspecialchars($articleType); ?></A>&nbsp;
	</TD>
	<TD ALIGN="CENTER">
		<A HREF="/<?php p($ADMIN); ?>/article_types/fields/?f_article_type=<?php  print urlencode($articleType); ?>"><?php echo $translator->trans('Fields'); ?></A>
	</TD>

	<TD>
		<?php  print $currentArticleType->getDisplayName(); ?> <?php print $currentArticleType->getDisplayNameLanguageCode(); ?>&nbsp;
	</TD>

	<td>
		<a href="javascript: void(0);" onclick="HideAll(type_ids); ShowElement('translate_type_<?php p($i); ?>');"><img src="<?php echo $Campsite["ADMIN_IMAGE_BASE_URL"]; ?>/localizer.png" alt="<?php echo $translator->trans("Translate"); ?>" title="<?php echo $translator->trans("Translate"); ?>" border="0"></a>
	</td>

	<TD ALIGN="CENTER">
		<A HREF="/<?php p($ADMIN); ?>/article_types/do_hide.php?f_article_type=<?php  print urlencode($articleType); ?>&f_status=<?php print $hideShowStatus; ?>&<?php echo SecurityToken::URLParameter(); ?>" onclick="return confirm('<?php echo $translator->trans('Are you sure you want to $1 the article type $2?', array('$1' => $hideShowText, '$2' => "\'".htmlspecialchars($articleType)."\'"), 'article_types'); ?>');"><IMG SRC="<?php echo $Campsite["ADMIN_IMAGE_BASE_URL"]; ?>/<?php echo $hideShowImage; ?>" BORDER="0" ALT="<?php  echo $translator->trans('$1 article type $2', array('$1' => ucfirst($hideShowText), '$2' => htmlspecialchars($articleType)), 'article_types'); ?>" TITLE="<?php  echo $translator->trans('$1 article type $2', array('$1' => ucfirst($hideShowText), '$2' => htmlspecialchars($articleType)), 'article_types'); ?>" ></A>
	</TD>

    <TD ALIGN="CENTER">
        <A HREF="/<?php p($ADMIN); ?>/article_types/do_filter.php?f_article_type=<?php print urlencode($articleType); ?>&f_filter=<?php echo($filterChangeValue); ?>&<?php echo SecurityToken::URLParameter(); ?>" onclick="return confirm('<?php echo $translator->trans('Are you sure you want to $1 articles of article type $2?', array('$1' => $filterChangeText, '$2' => "\'".htmlspecialchars($articleType)."\'"), 'article_types'); ?>');"><IMG SRC="<?php echo $Campsite["ADMIN_IMAGE_BASE_URL"]; ?>/<?php echo $filterImage; ?>" BORDER="0" ALT="<?php  echo $translator->trans('$1 articles of article type $2', array('$1' => ucfirst($filterChangeText), '$2' => htmlspecialchars($articleType)), 'article_types'); ?>" TITLE="<?php  echo $translator->trans('$1 articles of article type $2', array('$1' => ucfirst($filterChangeText), '$2' => htmlspecialchars($articleType)), 'article_types'); ?>" ></A>
    </TD>

	<TD ALIGN="CENTER">
		<A HREF="/<?php p($ADMIN); ?>/article_types/do_comment_activation.php?f_article_type=<?php  print urlencode($articleType); ?>&<?php echo SecurityToken::URLParameter(); ?>" onclick="return confirm('<?php echo $translator->trans('Are you sure you want to $1 comments for article type $2?', array('$1' => $commentChangeText, '$2' => "\'".htmlspecialchars($articleType)."\'"), 'article_types'); ?>');"><IMG SRC="<?php echo $Campsite["ADMIN_IMAGE_BASE_URL"]; ?>/<?php echo $commentImage; ?>" BORDER="0" ALT="<?php  echo $translator->trans('$1 comments for article type $2', array('$1' => ucfirst($commentChangeText), '$2' => htmlspecialchars($articleType)), 'article_types'); ?>" TITLE="<?php  echo $translator->trans('$1 comments for article type $2', array('$1' => ucfirst($commentChangeText), '$2' => htmlspecialchars($articleType)), 'article_types'); ?>" ></A>
	</TD>

	<?php  if ($g_user->hasPermission("DeleteArticleTypes")) { ?>
	<TD ALIGN="CENTER">
		<A HREF="/<?php p($ADMIN); ?>/article_types/do_del.php?f_article_type=<?php  print urlencode($articleType); ?>&<?php echo SecurityToken::URLParameter(); ?>" onclick="return confirm('<?php echo $translator->trans('Are you sure you want to delete the article type $1?  WARNING: Deleting this article type will delete all the articles associated with this article type.', array('$1' => htmlspecialchars($articleType)), 'article_types'); ?>');"><IMG SRC="<?php echo $Campsite["ADMIN_IMAGE_BASE_URL"]; ?>/delete.png" BORDER="0" ALT="<?php  echo $translator->trans('Delete article type $1', array('$1' => htmlspecialchars($articleType)), 'article_types'); ?>" TITLE="<?php  echo $translator->trans('Delete article type $1.', array('$1' => htmlspecialchars($articleType)), 'article_types'); ?>" ></A>
	</TD>
	<?php  } ?>

	</TR>

    <tr id="translate_type_<?php p($i); ?>" style="display: none;"><td colspan="6">
    	<table>

		<?php
		$color2 = 0;
		$isFirstTranslation = true;
		$typeTranslations = $currentArticleType->getTranslations();
		foreach ($typeTranslations as $typeLanguageId => $typeTransName) {
		?>
		<TR <?php  if ($color2) { $color2 = 0; ?>class="list_row_even"<?php  } else { $color2 = 1; ?>class="list_row_odd"<?php  } ?>">
			<TD <?php if ($isFirstTranslation) { ?>style="border-top: 2px solid #8AACCE;"<?php } ?> valign="middle" align="center">
				<?php
				$typeLanguage = new Language($typeLanguageId);
				p($typeLanguage->getCode());
				?>
			</TD>
			<TD <?php if ($isFirstTranslation) { ?>style="border-top: 2px solid #8AACCE;"<?php } ?> valign="middle" align="left" width="450px">
				<?php
				echo htmlspecialchars($typeTransName);
				?>
			</TD>
			</tr>
			<?php
			$isFirstTranslation = false;
		}
		?>

    	<tr>
    	<td colspan="2">
        <FORM method="POST" action="/<?php echo $ADMIN; ?>/article_types/do_translate.php">
			<?php echo SecurityToken::FormParameter(); ?>
    		<input type="hidden" name="f_type_id" value="<?php p($articleType); ?>">
    		<table cellpadding="0" cellspacing="0" style="border-top: 1px solid #CFC467; border-bottom: 1px solid #CFC467; background-color: #FFFCDF ; padding-left: 5px; padding-right: 5px;" width="100%">
    		<tr>
    			<td align="left">
    				<table cellpadding="2" cellspacing="1">
    				<tr>
		    			<td><?php echo $translator->trans("Add translation:", array(), 'article_types'); ?></td>
		    			<td>
							<SELECT NAME="f_type_language_id" class="input_select" alt="select" emsg="<?php echo $translator->trans("You must select a language."); ?>">
							<option value="0"><?php echo $translator->trans("---Select language---"); ?></option>
							<?php
						 	foreach ($allLanguages as $tmpLanguage) {
						        if ($languageObj->getLanguageId() == $tmpLanguage->getLanguageId())
						            $selected = true;
						        else
						            $selected = false;
						 	    camp_html_select_option($tmpLanguage->getLanguageId(),
						 								$selected,
						 								$tmpLanguage->getNativeName());

					        }
							?>
							</SELECT>
		    			</td>
		    			<td><input type="text" name="f_type_translation_name" value="" class="input_text" size="15" alt="blank" emsg="<?php echo $translator->trans('You must enter a name for the type.', array(), 'article_types'); ?>"></td>
		    			<td><input type="submit" name="f_submit" value="<?php echo $translator->trans("Translate"); ?>" class="button"></td>
		    		</tr>
		    		</table>
		    	</td>
    		</tr>
    		</table>
    		</FORM>
    	</td>
    	</tr>
    	</table>
	</td>
    </tr>

	<?php  $i++; } // foreach  ?>
</TABLE>
<?php } else { ?>
	<BLOCKQUOTE>
	<LI><?php  echo $translator->trans('No article types.', array(), 'article_types'); ?></LI>
	</BLOCKQUOTE>
<?php } ?>
<?php camp_html_copyright_notice(); ?>
