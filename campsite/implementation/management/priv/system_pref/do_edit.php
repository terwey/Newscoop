<?php
camp_load_translation_strings("system_pref");
require_once($_SERVER['DOCUMENT_ROOT']."/classes/SystemPref.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/classes/Input.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/classes/Log.php');

// Check permissions
if (!$g_user->hasPermission('ChangeSystemPreferences')) {
	camp_html_display_error(getGS("You do not have the right to change system preferences."));
	exit;
}

$f_keyword_separator = Input::Get('f_keyword_separator');
$f_login_num = Input::Get('f_login_num', 'int');
$f_external_subs_management = Input::Get('f_external_subs_management');

if (!Input::IsValid()) {
	camp_html_display_error(getGS('Invalid input: $1', Input::GetErrorString()), $_SERVER['REQUEST_URI']);
	exit;
}

SystemPref::Set("KeywordSeparator", $f_keyword_separator);
if ($f_login_num >= 0) {
	SystemPref::Set("LoginFailedAttemptsNum", $f_login_num);
}
SystemPref::Set('ExternalSubscriptionManagement', $f_external_subs_management);

camp_html_add_msg(getGS("System preferences updated."), "ok");
$logtext = getGS('System preferences updated');
Log::Message($logtext, $g_user->getUserId(), 171);

camp_html_goto_page("/$ADMIN/system_pref/");
?>
