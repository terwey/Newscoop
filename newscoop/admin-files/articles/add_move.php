<?php
require_once($GLOBALS['g_campsiteDir']. "/$ADMIN_DIR/articles/article_common.php");
require_once($GLOBALS['g_campsiteDir']. "/$ADMIN_DIR/articles/editor_load_countable.php");
require_once($GLOBALS['g_campsiteDir']. "/classes/ArticleType.php");

$translator = \Zend_Registry::get('container')->getService('translator');
$publications = Publication::GetPublications();

global $Campsite;

if (!$g_user->hasPermission('AddArticle')) {
	camp_html_display_error($translator->trans("You do not have the right to add articles."));
	exit;
}

// The article location dropdowns cause this page to reload,
// so we need to preserve the state with each refresh.
$f_article_name = Input::Get('f_article_name', 'string', '', true);
$f_article_type = Input::Get('f_article_type', 'string', '', true);
$f_article_language = Input::Get('f_article_language', 'int', 0, true);

// For choosing the article location.
$f_destination_publication_id = Input::Get('f_destination_publication_id', 'int', 0, true);
$f_destination_issue_number = Input::Get('f_destination_issue_number', 'int', 0, true);
$f_destination_section_number = Input::Get('f_destination_section_number', 'int', 0, true);

if (!Input::IsValid()) {
	camp_html_display_error($translator->trans('Invalid input: $1', array('$1' => Input::GetErrorString())), $_SERVER['REQUEST_URI']);
	exit;
}

if ($f_article_language <= 0) {
	$f_destination_publication_id = 0;
	$f_destination_issue_number = 0;
	$f_destination_section_number = 0;
}

if (count($publications) == 1) {
    $singlePublication = camp_array_peek($publications);
    $f_destination_publication_id = $singlePublication->getPublicationId();
    $f_article_language = $singlePublication->getDefaultLanguageId();
}


$allIssues = array();
if ($f_destination_publication_id > 0) {
	$allIssues = Issue::GetIssues($f_destination_publication_id,
								  $f_article_language, null, null, null, false,
								  array("ORDER BY" => array("Number" => "DESC")), true);
    if (count($allIssues) == 1) {
        $singleIssue = camp_array_peek($allIssues);
        $f_destination_issue_number = $singleIssue->getIssueNumber();
        $f_article_language = $singleIssue->getLanguageId();
    }
}

$allSections = array();
if ($f_destination_issue_number > 0) {
	$selectedIssue = new Issue($f_destination_publication_id, $f_article_language, $f_destination_issue_number);
	$allSections = Section::GetSections($f_destination_publication_id, $f_destination_issue_number, $f_article_language, null, null, array("ORDER BY" => array("Name" => "ASC")), true);
	if (count($allSections) == 1) {
	    $singleSection = camp_array_peek($allSections);
	    $f_destination_section_number = $singleSection->getSectionNumber();
	}
}

$allArticleTypes = ArticleType::GetArticleTypes();
$allLanguages = Language::GetLanguages(null, null, null, array(), array(), true);

$crumbs = array();
$crumbs[] = array($translator->trans("Actions"), "");
$crumbs[] = array($translator->trans("Add new article"), "");
echo camp_html_breadcrumbs($crumbs);

?>

<?php
if (sizeof($allArticleTypes) == 0) {
?>
<p>
<table border="0" cellspacing="0" cellpadding="0" class="box_table">
<tr>
	<td align="center">
	<font color="red">
	<?php echo $translator->trans("No article types were defined. You must create an article type first.", array(), 'articles'); ?>
	</font>
	<p><b><a href="/<?php echo $ADMIN; ?>/article_types/"><?php echo $translator->trans("Edit article types", array(), 'articles'); ?></a></b></p>
	</td>
</tr>
</table>
<?php
} else {
	include_once($GLOBALS['g_campsiteDir']."/$ADMIN_DIR/javascript_common.php");
	camp_html_display_msgs();
?>
<P>
<FORM NAME="add_article" METHOD="GET" ACTION="" onsubmit="return <?php camp_html_fvalidate(); ?>;">
<?php echo SecurityToken::FormParameter(); ?>

<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" class="box_table">
<TR>
	<TD COLSPAN="2">
		<B><?php  echo $translator->trans("Add new article"); ?></B>
		<HR NOSHADE SIZE="1" COLOR="BLACK">
	</TD>
</TR>
<TR>
	<td valign="top">
		<table>
		<tr>
			<TD ALIGN="RIGHT" ><?php  echo $translator->trans("Title", array(), 'api'); ?>:</TD>
			<TD>
			<INPUT TYPE="TEXT" NAME="f_article_name" SIZE="40" MAXLENGTH="140" class="input_text countable" alt="blank" emsg="<?php echo $translator->trans('You must fill in the $1 field.', array('$1' => $translator->trans('Title', array(), 'api'))); ?>" value="<?php echo htmlspecialchars($f_article_name); ?>">
			</TD>
		</TR>
		<TR>
			<TD ALIGN="RIGHT" ><?php  echo $translator->trans("Type"); ?>:</TD>
			<TD>
			    <?php if (count($allArticleTypes) == 1) { ?>
			        <INPUT TYPE="HIDDEN" NAME="f_article_type" VALUE="<?php echo $allArticleTypes[0]; ?>">
                    <?php
                        $tmpAT = new ArticleType($allArticleTypes[0]);
                        echo $tmpAT->getDisplayName($f_article_language);
			    } else { ?>
    				<SELECT NAME="f_article_type" class="input_select" alt="select" emsg="<?php echo $translator->trans('You must fill in the $1 field.', array('$1' => $translator->trans('Article Type', array(), 'articles'))); ?>">
	   		      	<option></option>
		  		    <?php
    				foreach ($allArticleTypes as $tmpType) {
	       			    $tmpAT = new ArticleType($tmpType);
			     	    camp_html_select_option($tmpType, $f_article_type, $tmpAT->getDisplayName($f_article_language));
				    }
					?>
				    </SELECT>
               <?php } ?>
			</TD>
		</TR>
		<TR>
			<TD ALIGN="RIGHT" ><?php  echo $translator->trans("Language"); ?>:</TD>
			<TD>
				<script>
				function on_language_select(p_select)
				{
					p_select.form.submit();
				}
				</script>
				<SELECT NAME="f_article_language" alt="select" emsg="<?php echo $translator->trans("You must select a language.");?>" class="input_select" onchange="on_language_select(this);">
				<option value="0"><?php echo $translator->trans("---Select language---"); ?></option>
				<?php
			 	foreach ($allLanguages as $tmpLanguage) {
			 		camp_html_select_option($tmpLanguage->getLanguageId(),
			 								$f_article_language,
			 								$tmpLanguage->getNativeName());
		        }
				?>
				</SELECT>
			</TD>
		</TR>
		</table>
	</td>

	<?php if ($g_user->hasPermission("MoveArticle")) { ?>
	<td style="border-left: 1px solid black;">
		<TABLE>
		<TR>
			<td colspan="2" style="padding-left: 20px; padding-bottom: 5px;font-size: 10pt; font-weight: bold;"><?php  echo $translator->trans("Select section:", array(), 'articles'); ?> <?php echo $translator->trans("(optional)"); ?></TD>
		</TR>
		<TR>
			<TD VALIGN="middle" ALIGN="RIGHT" style="padding-left: 20px;"><?php  echo $translator->trans('Publication'); ?>: </TD>
			<TD valign="middle" ALIGN="LEFT">
				<?php if ( count($publications) == 0) { ?>
					<SELECT class="input_select" DISABLED><OPTION><?php  echo $translator->trans('No publications'); ?></option></SELECT>
				<?php } elseif (count($publications) == 1) {
				    echo htmlspecialchars($singlePublication->getName());
				    ?>
				    <input type="hidden" name="f_destination_publication_id" value="<?php p($singlePublication->getPublicationId()); ?>">
				    <?php
				} else { ?>
    				<SELECT NAME="f_destination_publication_id" class="input_select" ONCHANGE="if (this.options[this.selectedIndex].value != <?php p($f_destination_publication_id); ?>) {this.form.submit();}" <?php if ($f_article_language == 0) { echo "disabled"; } ?>>
    				<OPTION VALUE="0"><?php  echo $translator->trans('---Select publication---'); ?></option>
    				<?php
    				foreach ($publications as $tmpPublication) {
    					camp_html_select_option($tmpPublication->getPublicationId(), $f_destination_publication_id, $tmpPublication->getName());
    				}
    				?>
    				</SELECT>
    				<?php
				}
				?>
			</td>
		</tr>

		<tr>
			<TD VALIGN="middle" ALIGN="RIGHT" style="padding-left: 20px;"><?php  echo $translator->trans('Issue'); ?>: </TD>
			<TD valign="middle" ALIGN="LEFT">
				<?php
				if (($f_destination_publication_id > 0) && (count($allIssues) > 0)) {
				    if (count($allIssues) == 1) {
				        echo htmlspecialchars($singleIssue->getName());
                        ?>
                        <input type="hidden" name="f_destination_issue_number" value="<?php p($singleIssue->getIssueNumber()); ?>">
                        <?php
				    } else {
    					?>
    					<SELECT NAME="f_destination_issue_number" class="input_select" ONCHANGE="if (this.options[this.selectedIndex].value != <?php p($f_destination_issue_number); ?>) { this.form.submit(); }">
    					<OPTION VALUE="0"><?php  echo $translator->trans('---Select issue---'); ?></option>
    					<?php
    					foreach ($allIssues as $tmpIssue) {
    						camp_html_select_option($tmpIssue->getIssueNumber(), $f_destination_issue_number, $tmpIssue->getName());
    					}
    					?>
    					</SELECT>
    					<?php
				    }
				} else {
					echo $translator->trans('No issues');
				}
				?>
			</td>
		</tr>

		<tr>
			<TD VALIGN="middle" ALIGN="RIGHT" style="padding-left: 20px;"><?php  echo $translator->trans('Section'); ?>: </TD>
			<TD valign="middle" ALIGN="LEFT">
				<?php
				if (($f_destination_publication_id > 0)
				    && (count($allIssues) > 0)
					&& ($f_destination_issue_number > 0)
					&& (count($allSections) > 0)) {

					if (count($allSections) == 1) {
					    echo htmlspecialchars($singleSection->getName());
					    ?>
					    <input type="hidden" name="f_destination_section_number" value="<?php p($singleSection->getSectionNumber()); ?>">
					    <?php
					} else {
				      ?>

        				<SELECT NAME="f_destination_section_number" class="input_select">
        				<OPTION VALUE="0"><?php  echo $translator->trans('---Select section---'); ?>
        				<?php
        				foreach ($allSections as $tmpSection) {
        					camp_html_select_option($tmpSection->getSectionNumber(), $f_destination_section_number, $tmpSection->getName());
        				}
        				?>
        				</SELECT>
        				<?php
	   			     }
			    } else {
					echo $translator->trans('No sections');
				}
				?>
				</TD>
		</tr>
		</TABLE>
	</td>
	<?php } ?>
</tr>
<TR>
	<TD COLSPAN="2" align="center">
		<HR NOSHADE SIZE="1" COLOR="BLACK">
		<INPUT TYPE="submit" NAME="save" VALUE="<?php  echo $translator->trans('Save'); ?>" class="button" onclick="document.forms.add_article.action='do_add.php';">
	</TD>
</TR>
</TABLE>
</FORM>
<P>
<script>
document.add_article.f_article_name.focus();
</script>
<?php } ?>
<?php camp_html_copyright_notice(); ?>
