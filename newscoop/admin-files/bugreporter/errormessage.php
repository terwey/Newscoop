<?php
require_once($GLOBALS['g_campsiteDir'].'/classes/Language.php');
$translator = \Zend_Registry::get('container')->getService('translator');

global $Campsite;
global $ADMIN_DIR;
global $ADMIN;
?>
<br>
<br>
<table border="0">
<tr>
    <td rowspan="2" valign="top">
        <script type="text/javascript" src="<?php echo $Campsite['WEBSITE_URL']; ?>/js/campsite.js"></script>

        <form name="dialog" method="POST" action="/<?php echo $ADMIN; ?>/bugreporter/senderrorform.php" />

        <input type="hidden" name="f_is_post_from_bugreporter" value="1">
        <input type="hidden" name="f_str" value="<?php echo htmlspecialchars($reporter->getStr()); ?>">
        <input type="hidden" name="f_num" value="<?php echo htmlspecialchars($reporter->getErrorNum()); ?>">
        <input type="hidden" name="f_time" value="<?php echo htmlspecialchars($reporter->getTime()); ?>">
        <input type="hidden" name="f_file" value="<?php echo htmlspecialchars($reporter->getFile()); ?>">
        <input type="hidden" name="f_line" value="<?php echo htmlspecialchars($reporter->getLine()); ?>">
        <input type="hidden" name="f_backtrace" value="<?php echo htmlspecialchars($reporter->getBacktraceString()); ?>">
        <table border="0" cellpadding="0" cellspacing="0" class="box_table">
        <tr>
            <td align="left">
                <table border="0" cellspacing="0" cellpadding="3" align="left">
                <tr>
                    <td colspan="2"><b><font color="red"><?php echo $translator->trans("Error Report", array(), 'bug_reporting'); ?></font></b>
                        <hr noshade size="1" color="black">
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <p><?php
                            echo $translator->trans("Newscoop has encountered a problem.", array(), 'bug_reporting');
                            echo $translator->trans("We are sorry for the inconvenience.", array(), 'bug_reporting');
                            ?></p>
                        <p><?php
                           echo $translator->trans("In order to help the Newscoop team investigate and pinpoint the issue please take a moment to describe what happened.", array(), 'bug_reporting');
                           ?></p>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">&nbsp;
                    </td>
                </tr>
                <tr valign="top">
                    <td colspan="2">
                    	<?php echo $translator->trans("What were you trying to do when this happened?", array(), 'bug_reporting'); ?> <?php echo $translator->trans("(optional)"); ?>
                    </td>
                </tr>
                <tr>
                    <td align="left" nowrap>&nbsp;</td>
                    <td>
                        <textarea cols="50" rows="4" class="input_text" name="f_description"></textarea>
                    </td>
                </tr>
                <tr>
                    <td align="left" nowrap>
                    	<?php echo $translator->trans("Email:");  ?> <?php echo $translator->trans("(optional)"); ?>
                    </td>
                    <td><input type="text" class="input_text" name="f_email" value="" size="32" maxlength="255">
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr id="user_details_show_link">
            <td style="padding-left: 6px; padding-top: 6px;">
                <a href=
                "javascript:%20void(0);" onclick=
                "ToggleRowVisibility('user_details_dialog');
                ToggleRowVisibility('user_details_hide_link');
                ToggleRowVisibility('user_details_show_link');">
                <img src="<?php echo $Campsite["ADMIN_IMAGE_BASE_URL"] . "/viewmagplus.png"; ?>" id="my_icon" border=
                "0" align="center"><?php echo $translator->trans("Show error details", array(), 'bug_reporting'); ?></a>
            </td>
        </tr>
        <tr id="user_details_hide_link" style="display: none;">
            <td style="padding-left: 6px; padding-top: 6px;"><a href=
                "javascript:%20void(0);" onclick=
                "ToggleRowVisibility('user_details_dialog');
                ToggleRowVisibility('user_details_hide_link');
                ToggleRowVisibility('user_details_show_link');">
                <img src="<?php echo $Campsite["ADMIN_IMAGE_BASE_URL"] . "/viewmagminus.png"; ?>" id="my_icon" border="0" align="center"><?php echo $translator->trans("Hide error details", array(), 'bug_reporting'); ?></a>
            </td>
        </tr>
        <tr id="user_details_dialog" style="display: none;">
            <td>
                <table border="0" cellspacing="0" cellpadding="3" align="center"
                width="100%">
                <tr>
                    <td align="left" nowrap>
                    	<?php echo $translator->trans("Error ID:", array(), 'bug_reporting'); ?>
                    </td>
                    <td>
                    	<?php echo $reporter->getId(); ?>
                    </td>
                </tr>
                <tr>
                    <td align="left" nowrap>
                    	<?php echo $translator->trans("Software:", array(), 'bug_reporting'); ?>
                    </td>
                    <td>
                    	<?php echo $reporter->getSoftware();  ?>
                    </td>
                </tr>
                <tr>
                    <td align="left" nowrap>
                    	<?php echo $translator->trans("Error Message:", array(), 'bug_reporting'); ?>
                    </td>
                    <td>
                    	<?php echo $reporter->getStr(); ?>
                    </td>
                </tr>
                <tr>
                    <td align="left" nowrap>
                    	<?php echo $translator->trans("Error Number:", array(), 'bug_reporting'); ?>
                    </td>
                    <td>
                    	<?php echo $reporter->getErrorNum(); ?>
                    </td>
                </tr>
                <tr>
                    <td align="left" nowrap>
                    	<?php echo $translator->trans("Time:"); ?>
                    </td>
                    <td>
                    	<?php echo $reporter->getTime(); ?>
                    </td>
                </tr>
                <tr valign="top">
                    <td align="left" nowrap>
                    	<?php echo $translator->trans("Backtrace:", array(), 'bug_reporting'); ?>
                    </td>
                    <td>
                        <div style="overflow: auto; height: 150px;
                        background-color: #EEEEEE; border: 1px solid
                        black; padding-left: 3px;"><pre><?php
                            echo $reporter->getBacktraceString();
                        ?></pre>
                        &nbsp;
                    </td>
                </table>
            </td>
    </tr>
    <tr>
            <td>
                <table border="0" cellspacing="0" cellpadding="6" align="center"
                width="100%">
                <tr>
                    <td colspan="2">
                        <div align="center"><input type="submit"
                        class="button" value="<?php echo $translator->trans("Send"); ?>"></div>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        </table>
        </form>
    </td>
</tr>
</table>
