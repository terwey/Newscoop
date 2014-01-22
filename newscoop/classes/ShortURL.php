<?php
/**
 * @package Campsite
 */

/**
 * Includes
 */
require_once($GLOBALS['g_campsiteDir'].'/db_connect.php');
require_once($GLOBALS['g_campsiteDir'].'/classes/Alias.php');
require_once($GLOBALS['g_campsiteDir'].'/classes/Publication.php');
require_once($GLOBALS['g_campsiteDir'].'/classes/Issue.php');
require_once($GLOBALS['g_campsiteDir'].'/classes/Section.php');
require_once($GLOBALS['g_campsiteDir'].'/classes/Article.php');
require_once($GLOBALS['g_campsiteDir'].'/classes/Language.php');

class ShortURL
{
	public static function GetURL($p_publicationId, $p_languageId = null,
	                              $p_issueNo = null, $p_sectionNo = null,
	                              $p_articleNo = NULL, $p_port = null)
	{
		global $g_ado_db;
		global $_SERVER;

		$translator = \Zend_Registry::get('container')->getService('translator');

		if (is_null($p_port)) {
			if (!isset($_SERVER['SERVER_PORT']))
			{
				$_SERVER['SERVER_PORT'] = 80;
			}
			$p_port = $_SERVER['SERVER_PORT'];
		}

		$publicationObj = new Publication($p_publicationId);
		if (!$publicationObj->exists()) {
			return new PEAR_Error($translator->trans('Publication does not exist.'));
		}
		if (!isset($p_languageId)) {
			$p_languageId = $publicationObj->getDefaultLanguageId();
		}
		$scheme = $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';
		$defaultAlias = new Alias($publicationObj->getDefaultAliasId());
		$uri = ShortURL::GetURI($p_publicationId, $p_languageId, $p_issueNo, $p_sectionNo, $p_articleNo);
		if (!is_string($uri) && PEAR::isError($uri)) {
			return $uri;
		}
		return $scheme . $defaultAlias->getName() . $uri;
	}

	public static function GetURI($p_publicationId, $p_languageId,
	                              $p_issueNo = null, $p_sectionNo = null,
	                              $p_articleNo = null)
	{
		$translator = \Zend_Registry::get('container')->getService('translator');

		$languageObj = new Language($p_languageId);
		if (!$languageObj->exists()) {
			return new PEAR_Error($translator->trans('Language does not exist.'));
		}
		$uri = $GLOBALS['Campsite']['SUBDIR'] . '/' . $languageObj->getCode() . '/';
		if (!is_null($p_issueNo) && is_null($p_articleNo)) {
			$issueObj = new Issue($p_publicationId, $p_languageId, $p_issueNo);
			if (!$issueObj->exists()) {
				return new PEAR_Error($translator->trans('Issue does not exist.'));
			}
			$uri .= $issueObj->getUrlName() . '/';
		}
		if (!is_null($p_sectionNo) && is_null($p_articleNo)) {
			$sectionObj = new Section($p_publicationId, $p_issueNo, $p_languageId, $p_sectionNo);
			if (!$sectionObj->exists()) {
				return new PEAR_Error($translator->trans('Section does not exist.'));
			}
			$uri .= $sectionObj->getUrlName() . '/';
		}
		if (!is_null($p_articleNo)) {
			$articleObj = new Article($p_languageId, $p_articleNo);

			if (!$articleObj->exists()) {
				return new PEAR_Error($translator->trans('Article does not exist.'));
			}
			$issueObj = new Issue($p_publicationId, $p_languageId, $articleObj->getIssueNumber());
			$sectionObj = new Section($p_publicationId, $articleObj->getIssueNumber(), $p_languageId,
			$articleObj->getSectionNumber());
			$uri .= $issueObj->getUrlName() . '/';
			$uri .= $sectionObj->getUrlName() . '/';
			$uri .= $articleObj->getUrlName() . '/';
		}
		return $uri;
	}
}

?>
