<?php

require_once($GLOBALS['g_campsiteDir']. "/$ADMIN_DIR/users/users_common.php");
require_once($GLOBALS['g_campsiteDir']. "/classes/Log.php");

$translator = \Zend_Registry::get('container')->getService('translator');

if (!SecurityToken::isValid()) {
    camp_html_display_error($translator->trans('Invalid security token!'));
    exit;
}

read_user_common_parameters(); // $uType, $userOffs, $ItemsPerPage, search parameters
$uType = 'Subscribers';
compute_user_rights($g_user, $g_canManage, $g_canDelete);
if (!$g_canManage) {
	camp_html_display_error($translator->trans('You do not have the right to change user account information.', array(), 'users'));
	exit;
}

// read input
$g_userId = Input::Get('User', 'int', 0);
$g_editUser = new User($g_userId);
if ($g_editUser->getUserName() == '') {
	camp_html_display_error($translator->trans('No such user account.', array(), 'users'));
	exit;
}
$g_backLink = "/$ADMIN/users/edit.php?uType=Subscribers&User=$g_userId";
$g_cStartIP1 = Input::Get('cStartIP1', 'int', -1);
$g_cStartIP2 = Input::Get('cStartIP2', 'int', -1);
$g_cStartIP3 = Input::Get('cStartIP3', 'int', -1);
$g_cStartIP4 = Input::Get('cStartIP4', 'int', -1);
$g_cAddresses = Input::Get('cAddresses', 'int', 0);

// check if input was correct
if ($g_cStartIP1 == -1 || $g_cStartIP2 == -1 || $g_cStartIP3 == -1 || $g_cStartIP4 == -1) {
	camp_html_display_error($translator->trans('You must fill in the $1 field.', array('$1' => 'Start IP')),
		$g_backLink);
	exit;
}
if ($g_cAddresses == 0) {
	$g_errorMsg = $translator->trans('You must fill in the $1 field.', array('$1' => 'Number of addresses'));
	camp_html_display_error($g_errorMsg, $g_backLink);
	exit;
}

// check if the IP address group exists already
$ipAddressArray = array($g_cStartIP1, $g_cStartIP2, $g_cStartIP3, $g_cStartIP4);
$ipAccess = new IPAccess($g_userId, $ipAddressArray, $g_cAddresses);
if ($ipAccess->exists()) {
	$g_errorMsg = $translator->trans('The IP address group $1:$2 conflicts with another existing group.', array(
		'$1' => $ipAccess->getStartIPstring(), '$2' => $g_cAddresses), 'users');
	camp_html_display_error($g_errorMsg, $g_backLink);
	exit;
}

if (!$ipAccess->create($g_userId, $ipAddressArray, $g_cAddresses)) {
	camp_html_display_error($translator->trans('There was an error creating the IP address group.', array('$1' => "$g_startIPStr:$g_cAddresses"), 'users'), $g_backLink);
	exit;
}

camp_html_add_msg($translator->trans("The IP Group $1 has been created.", array('$1' => $ipAccess->getStartIPstring().":$g_cAddresses"), 'users'), "ok");
camp_html_goto_page($g_backLink);

?>