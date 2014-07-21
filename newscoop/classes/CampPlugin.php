<?php
/**
 * @package Campsite
 *
 * @author Sebastian Goebel <devel@yellowsunshine.de>
 * @copyright 2007 MDLF, Inc.
 * @license http://www.gnu.org/licenses/gpl.txt
 * @version $Revision$
 * @link http://www.sourcefabric.org
 */

require_once dirname(__FILE__) . '/DatabaseObject.php';
require_once dirname(__FILE__) . '/../template_engine/metaclasses/MetaAction.php';

/**
 * Class CampPlugin
 */
class CampPlugin extends DatabaseObject
{
	const CACHE_KEY_PLUGINS_LIST = 'campsite_plugins_list';

	const CACHE_KEY_PLUGINS_ALL = 'campsite_plugins_all';

    public $m_keyColumnNames = array('Name');

    public $m_dbTableName = 'Plugins';

    public $m_columnNames = array('Name', 'Version', 'Enabled');

    static private $m_allPlugins = null;

    static protected $m_pluginsInfo = null;

    public function CampPlugin($p_name = null, $p_version = null, $enabled = null)
    {
        parent::DatabaseObject($this->m_columnNames);
        $this->m_data['Name'] = $p_name;

        if (!is_null($p_version)) {
            $this->m_data['Version'] = $p_version;
        }

        if (!is_null($enabled)) {
            $this->m_data['Enabled'] = $enabled;
        }

        if (!is_null($p_name) && is_null($enabled)) {
            $this->fetch();
        }
    } // constructor

    public function create($p_name = null, $p_version = null, $p_enabled = true)
    {
        // Create the record
        $this->m_data['Name'] = $p_name;

        $values = array(
            'Version' => $p_version,
            'Enabled' => $p_enabled ? 1 : 0
        );


        $success = parent::create($values);
        if (!$success) {
            return false;
        }
    }

    static public function GetAll($p_reload = false)
    {
        if (!$p_reload && is_array(self::$m_allPlugins)) {
        	return self::$m_allPlugins;
        }

        if (!$p_reload && CampCache::IsEnabled()) {
            $cacheListObj = new CampCacheList(array(), self::CACHE_KEY_PLUGINS_ALL);
            self::$m_allPlugins = $cacheListObj->fetchFromCache();
            if (self::$m_allPlugins !== false && is_array(self::$m_allPlugins)) {
                return self::$m_allPlugins;
            }
        }

        self::$m_allPlugins = array();
        $pluginService = \Zend_Registry::get('container')->get('newscoop.plugins.service');
        foreach ($pluginService->getAllAvailablePlugins() as $key => $plugin) {
            self::$m_allPlugins[] = new CampPlugin($plugin->getName(), $plugin->getVersion(), $plugin->getEnabled());
        }

        if (!$p_reload && CampCache::IsEnabled()) {
            $cacheListObj->storeInCache(self::$m_allPlugins);
        }

        return self::$m_allPlugins;
    }

    static public function GetEnabled($p_reload = false)
    {
        $plugins = array();

        foreach (self::GetAll($p_reload) as $CampPlugin) {
            if ($CampPlugin->isEnabled()) {
                $plugins[] = $CampPlugin;
            }
        }
        return $plugins;
    }

    public function getBasePath()
    {
        return CS_PLUGINS_DIR.DIR_SEP.$this->getName();
    }

    public function getName()
    {
        return $this->getProperty('Name');
    }

    public function getDbVersion()
    {
        return $this->getProperty('Version');
    }


    public function getFsVersion()
    {
        $info = self::GetPluginsInfo();
        if (isset($info[$this->getName()]['version'])) {
            return $info[$this->getName()]['version'];
        }
        return NULL;
    }

    public function isEnabled()
    {
        return $this->getProperty('Enabled') == 1 ? true : false;
    }

    static public function IsPluginEnabled($p_name, $p_version = null)
    {
        $plugin = new CampPlugin($p_name, $p_version);

        return $plugin->isEnabled();
    }

    public function install()
    {
        $info = $this->getPluginInfo();
        if (function_exists($info['install'])) {
            call_user_func($info['install']);
        }
        MetaAction::DeleteActionsFromCache();
        self::ClearPluginsInfo();
    }

    public function enable()
    {
        $this->setProperty('Enabled', 1);

        $info = $this->getPluginInfo();
        if (function_exists($info['enable'])) {
            call_user_func($info['enable']);
        }
        MetaAction::DeleteActionsFromCache();
        self::ClearPluginsInfo();
    }

    public function disable()
    {
        $this->setProperty('Enabled', 0);

        $info = $this->getPluginInfo();
        if (function_exists($info['disable'])) {
            call_user_func($info['disable']);
        }
        MetaAction::DeleteActionsFromCache();
        self::ClearPluginsInfo();
    }

    public function uninstall()
    {
        $info = $this->getPluginInfo();
        if (function_exists($info['uninstall'])) {
            call_user_func($info['uninstall']);
        }

        self::ClearPluginsInfo();

        $this->delete();
        MetaAction::DeleteActionsFromCache();
        self::ClearPluginsInfo();
    }

    public function update($p_columns = NULL, $p_commit = true, $p_isSql = false)
    {
        $info = $this->getPluginInfo();
        if (function_exists($info['update'])) {
            call_user_func($info['update']);
        }
    }

    /**
     * Return a list or available or activated plugins.
     * The method have to return an (empty) array.
     *
     * @param boolean $p_selectEnabled
     * @param boolean $p_reload
     * @return array
     */
    static public function GetPluginsInfo($p_selectEnabled = false, $p_reload = false)
    {
        $p_selectEnabled = $p_selectEnabled ? 'enabled' : 'available';

        if ($p_reload) {
            self::FetchFilePluginsInfo();
        }

        if (is_array(self::$m_pluginsInfo) && is_array(self::$m_pluginsInfo[$p_selectEnabled])) {
            return self::$m_pluginsInfo[$p_selectEnabled];
        } else {
            if (self::FetchCachePluginsInfo() && is_array(self::$m_pluginsInfo[$p_selectEnabled])) {
                return self::$m_pluginsInfo[$p_selectEnabled];
            }
            if (self::FetchFilePluginsInfo() && is_array(self::$m_pluginsInfo[$p_selectEnabled])) {
                return self::$m_pluginsInfo[$p_selectEnabled];
            }
        }

        self::$m_pluginsInfo = array('available' => array(), 'enabled' => array());
        return array();
    }

    /**
     * Fetch plugin infos from the %plugin.info files.
     *
     * @return boolen plugins were found
     */
    private static function FetchFilePluginsInfo()
    {
        if (!is_dir(CS_PATH_PLUGINS)) {
            return false;
        }

        $pluginsInfo = array('available' => null, 'enabled' => array());

        $enabledPluginsNames = array();
        $enabledPlugins = self::GetEnabled();
        foreach ($enabledPlugins as $plugin) {
            $enabledPluginsNames[] = $plugin->getName();
        }

        foreach (glob(CS_PATH_PLUGINS . '/*/*.info.php') as $file) {
            include $file;
            $plugin = basename(dirname($file));
            $pluginsInfo['available'][$plugin] = $info;
            if (array_search($plugin, $enabledPluginsNames) !== false) {
                $pluginsInfo['enabled'][$plugin] = $info;
            }
        }

	    self::$m_pluginsInfo = $pluginsInfo;
        self::StoreCachePluginsInfo();

        if (is_array($pluginsInfo['available'])) {
			return true;
        };
        return false;
    }

    /**
     * Fetch plugin infos from cache.
     * The method have to validate if plugins still exists in filesystem.
     *
     * @return boolean plugins were found in cache
     */
    private static function FetchCachePluginsInfo()
    {
    	if (CampCache::IsEnabled()) {
    		$pluginsInfo = CampCache::singleton()->fetch(self::CACHE_KEY_PLUGINS_LIST);
    		if ($pluginsInfo !== false && is_array($pluginsInfo['available'])) {
    		    foreach ($pluginsInfo['available'] as $entry => $info) {
    		        if (!file_exists(CS_PATH_PLUGINS.DIR_SEP.$entry.DIR_SEP.$entry.'.info.php')) {
    		            unset($pluginsInfo['available'][$entry]);
    		            unset($pluginsInfo['enabled'][$entry]);
    		        }
    		    }
    			self::$m_pluginsInfo = $pluginsInfo;
    			return true;
    		}
    	}
    	return false;
    }


    private static function StoreCachePluginsInfo()
    {
    	if (CampCache::IsEnabled()) {
            return CampCache::singleton()->add(self::CACHE_KEY_PLUGINS_LIST, self::$m_pluginsInfo);
        }
        return false;
    }


    private static function DeleteCachePluginsInfo()
    {
        if (CampCache::IsEnabled()) {
        	$cacheListObj = new CampCacheList(array(), self::CACHE_KEY_PLUGINS_ALL);
        	$cacheListObj->deleteFromCache();
            return CampCache::singleton()->delete(self::CACHE_KEY_PLUGINS_LIST);
        }
        return false;
    }


    public static function ClearPluginsInfo()
    {
    	self::DeleteCachePluginsInfo();
        self::$m_pluginsInfo = null;
        self::$m_allPlugins = null;
    }


    public function getPluginInfo($p_plugin_name = '')
    {
        if (!empty($p_plugin_name)) {
            $name = $p_plugin_name;
        } elseif (isset($this) && is_a($this, 'CampPlugin')) {
            $name = $this->getName();
        } else {
            return false;
        }

        $infos = self::GetPluginsInfo();
        $info = $infos[$name];

        return $info;
    }

    static public function ExtendNoMenuScripts(&$p_no_menu_scripts)
    {
        foreach (self::GetPluginsInfo() as $info) {
            if (is_array($info['no_menu_scripts']) && CampPlugin::IsPluginEnabled($info['name'])) {
                $p_no_menu_scripts = array_merge($p_no_menu_scripts, $info['no_menu_scripts']);
            }
        }
    }

    static public function ExtractPackage($p_uploaded_package, &$p_log = null)
    {
        $plugin_name = false;

        $translator = \Zend_Registry::get('container')->getService('translator');
        require_once('Archive/Tar.php');
        $tar = new Archive_Tar($p_uploaded_package);


        if (($file_list = $tar->ListContent()) == 0) {
            $p_log = $translator->trans('The uploaded file format is unsupported.', array(), 'api');
            return false;
        } else {
            foreach ($file_list as $v) {

                if (preg_match('/[^\/]+\/([^.]+)\.info\.php/', $v['filename'], $matches)) {
                    $plugin_name = $matches[1];
                }

                #$p_log .= sprintf("Name: %s  Size: %d   modtime: %s mode: %s<br>", $v['filename'], $v['size'], $v['mtime'], $v['mode']);
            }
        }

        if ($plugin_name === false) {
            $p_log = $translator->trans('The uploaded archive does not contain an valid newscoop plugin.', array(), 'api');
            return false;
        }

        $tar->extract(CS_PATH_PLUGINS);

        self::ClearPluginsInfo();
        CampPlugin::GetPluginsInfo(false, true);
    }

    /**
     * @param string $p_filename
     * @param string $p_area
     * @deprecated
     */
    public static function PluginAdminHooks($p_filename, $p_area=null)
    {
        global $ADMIN, $ADMIN_DIR, $Campsite, $g_user;

        $paths = array();

        $filename = realpath($p_filename);
        $admin_path = realpath(CS_PATH_SITE.DIR_SEP.$ADMIN_DIR);
        $script = str_replace($admin_path, '', $filename);

        foreach (self::GetEnabled() as $plugin) {
            $filepath = realpath(dirname(APPLICATION_PATH).DIR_SEP.$plugin->getBasePath().DIR_SEP.'admin-files'.DIR_SEP.'include'.DIR_SEP.$script);
            if (file_exists($filepath))  {
                require_once $filepath;
            }
        }
    }

    /**
     * Includes hooks for this filename from plugins
     * @param string $filename
     * @param array $vars
     */
    public static function adminHook($filename, $vars=array())
    {
        global $ADMIN, $ADMIN_DIR, $Campsite, $g_user;

        $filename = realpath($filename);
        $admin_path = realpath(CS_PATH_SITE.DIR_SEP.$ADMIN_DIR);
        $script = str_replace($admin_path, '', $filename);

        foreach (array_keys($vars) as $var => $val )
            global $$var;
        extract($vars);

        foreach (self::GetEnabled() as $plugin) {
            $filepath = realpath(dirname(APPLICATION_PATH).DIR_SEP.$plugin->getBasePath().DIR_SEP.'admin-files'.DIR_SEP.'include'.DIR_SEP.$script);
            if (file_exists($filepath))  {
                require_once $filepath;
            }
        }
    }

    public static function GetNeedsUpdate()
    {
        $upgradable = false;

        foreach (self::GetEnabled(true) as $CampPlugin) {
            if ($CampPlugin->getFsVersion() != $CampPlugin->getDbVersion()) {
                $upgradable[$CampPlugin->getName()]  = array(
                    'db' => $CampPlugin->getDbVersion(),
                    'current' => $CampPlugin->getFsVersion()
                );
            }
        }
        return $upgradable;
    }

    /**
     * Updates plugins if needed
     * @return void
     */
    public static function OnUpgrade()
    {
        $plugins = self::GetNeedsUpdate();
        if (!is_array($plugins) || empty($plugins)) {
            return; // no plugin to update
        }

        // update
        foreach ($plugins as $name => $info) {
            $CampPlugin = new CampPlugin($name);
            if (empty($info['current'])) {
                continue;
            }
            $currentVersion = $CampPlugin->getFsVersion();
            if ($CampPlugin->getDbVersion() != $currentVersion) {
                $CampPlugin->delete();
                $CampPlugin->create($name, $currentVersion);
                $CampPlugin->update();
            }
        }

    }

    /**
     * Updates plugins environment if needed
     * @return void
     */
    public static function OnAfterUpgrade()
    {
        foreach (self::GetPluginsInfo() as $info) {
            if (isset($info['upgrade'])) {
                if (!isset($info['name'])) {
                    continue;
                }

                $enabled = self::IsPluginEnabled($info['name']);

                $upgrade_func_name = $info['upgrade'];
                if (function_exists($upgrade_func_name)) {
                    call_user_func($upgrade_func_name, $enabled);
                }
            }
        }

        // update autoload 
        exec('php '.$GLOBALS['g_campsiteDir'].'/scripts/newscoop.php autoload:update');
    }
}

?>
