<?php
/**
 * @package Campsite
 */

/**
 * Includes
 */
require_once($GLOBALS['g_campsiteDir'].'/db_connect.php');
require_once($GLOBALS['g_campsiteDir'].'/classes/DatabaseObject.php');
require_once($GLOBALS['g_campsiteDir'].'/classes/DbObjectArray.php');

/**
 * @package Campsite
 */
class UrlType extends DatabaseObject {
	var $m_dbTableName = 'URLTypes';
	var $m_keyColumnNames = array('Id');
	var $m_keyIsAutoIncrement = true;
	var $m_columnNames = array('Id', 'Name', 'Description');

	/**
	 * Constructor.
	 * @param int $p_id
	 */
	public function UrlType($p_id = null)
	{
		parent::DatabaseObject($this->m_columnNames);
		if (!is_null($p_id)) {
    		$this->m_data['Id'] = $p_id;
			$this->fetch();
		}
	} // constructor


	/**
	 * Return an array of all URL types.
	 * @return array
	 */
	public static function GetUrlTypes()
	{
		$queryStr = 'SELECT * FROM URLTypes';
		$urlTypes = DbObjectArray::Create('UrlType', $queryStr);
		return $urlTypes;
	} // fn GetUrlTypes


	/**
	 * The unique ID of the URLType.
	 * @return int
	 */
	public function getId()
	{
		return $this->m_data['Id'];
	} // fn getId


	/**
	 * Return the name of this URLType.
	 * @return string
	 */
	public function getName()
	{	
		$translator = \Zend_Registry::get('container')->getService('translator');
		$name = $this->m_data['Name'];
		switch ($name) {
			case "short names":
				return $translator->trans("short names", array(), 'api');
			case "template path":
				return $translator->trans("template path", array(), 'api');
			default:
				return "";
		}
	} // fn getName


	/**
	 * Return the description of the URL Type.
	 * @return string
	 */
	public function getDescription()
	{
		return $this->m_data['Description'];
	} // fn getDescription


	public static function GetByName($p_name)
	{
		global $g_ado_db;
		$sql = "SELECT * FROM URLTypes WHERE Name=".$g_ado_db->escape($p_name);
		$row = $g_ado_db->GetRow($sql);
		if ($row && is_array($row)) {
			$urlType = new UrlType();
			$urlType->fetch($row);
			return $urlType;
		} else {
			return null;
		}
	} // fn GetByName

} // class UrlType

?>
