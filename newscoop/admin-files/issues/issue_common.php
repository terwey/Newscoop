<?PHP
require_once($GLOBALS['g_campsiteDir'].'/classes/Input.php');
require_once($GLOBALS['g_campsiteDir'].'/classes/Publication.php');
require_once($GLOBALS['g_campsiteDir'].'/classes/Issue.php');
require_once($GLOBALS['g_campsiteDir'].'/classes/Language.php');
require_once($GLOBALS['g_campsiteDir'].'/classes/Log.php');

/**
 * Check if the given parameters match an existing issue.  All parameters
 * should be for the issue you are adding/editing.  If you are adding,
 * set $p_isExistingIssue to FALSE, and if you are editing, set it to TRUE.
 *
 * @param int $p_publicationId
 * @param int $p_issueNumber
 * @param int $p_languageId
 * @param string $p_urlName
 * @param boolean $p_isExistingIssue
 * 		Set this to true if the issue already exists.
 * @return string
 * 		Return empty string on success, error message on failure.
 */
function camp_is_issue_conflicting($p_publicationId, $p_issueNumber, $p_languageId, $p_urlName, $p_isExistingIssue)
{
	global $ADMIN;
	$translator = \Zend_Registry::get('container')->getService('translator');
	// The tricky part - language ID and URL name must be unique.
	$conflictingIssues = Issue::GetIssues($p_publicationId, $p_languageId, null, $p_urlName, null, false, null, true);
	$conflictingIssue = array_pop($conflictingIssues);

	// Check if the issue conflicts with another issue.

	// If the issue exists, we have to make sure the conflicting issue is not
	// itself.
	$isSelf = ($p_isExistingIssue && is_object($conflictingIssue)
			   && ($conflictingIssue->getIssueNumber() == $p_issueNumber));
	if (is_object($conflictingIssue) && !$isSelf) {
		$conflictingIssueLink = "/$ADMIN/issues/edit.php?"
			."Pub=$p_publicationId"
			."&Issue=".$conflictingIssue->getIssueNumber()
			."&Language=".$conflictingIssue->getLanguageId();

		$errMsg = $translator->trans('The language and URL name must be unique for each issue in this publication.', array(), 'issues')."<br>".$translator->trans('The values you are trying to set conflict with issue $1$2. $3 ($4)$5.', array(
			'$1' => "<a href='$conflictingIssueLink'>",
			'$2' => $conflictingIssue->getIssueNumber(),
			'$3' => $conflictingIssue->getName(),
			'$4' => $conflictingIssue->getLanguageName(),
			'$5' => '</a>'), 'issues');
		return $errMsg;
	}
	return "";
}
?>