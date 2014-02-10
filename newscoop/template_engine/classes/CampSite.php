<?php

/**
 * @package Campsite
 *
 * @author Holman Romero <holman.romero@gmail.com>
 * @author Mugur Rus <mugur.rus@gmail.com>
 * @copyright 2007 MDLF, Inc.
 * @license http://www.gnu.org/licenses/gpl.txt
 * @version $Revision$
 * @link http://www.sourcefabric.org
 */
use Newscoop\Service\Resource\ResourceId;
use Newscoop\Service\ITemplateSearchService;

/**
 * Class CampSite
 */
final class CampSite extends CampSystem
{

    /**
     * Class constructor
     */
    final public function __construct()
    {

    }// fn __construct

    /**
     * Initialises the context.
     *
     * After load the session, the application parse the current URI
     * and starts the context from the request parameters.
     *
     * @return void
     */
    public function init()
    {
        // returns when site is not in online mode
        if ($this->getSetting('site.online') == 'N') {
            return;
        }

        // gets the context
        CampTemplate::singleton()->refreshContext();
    }// fn initContext

    /**
     * Initialises the session.
     */
    public function initSession()
    {
        $session = CampSession::singleton();
    }// fn initSession

    /**
     * Loads the configuration options.
     *
     * @param string $p_configFile
     *      The path to the config file
     */
    public function loadConfiguration($p_configFile = null)
    {
        if (empty($p_configFile)) {
            $p_configFile = $GLOBALS['g_campsiteDir'] . '/conf/configuration.php';
        }
        CampConfig::singleton($p_configFile);
    }// fn loadConfiguration

    /**
     * Dispatches the site.
     *
     * Sets attribute values from site configuration to the document
     * to be displayed.
     *
     * @return void
     */
    public function dispatch()
    {
        $document = self::GetHTMLDocumentInstance();
        $config = self::GetConfigInstance();

        $document->setMetaTag('description',
                $config->getSetting('site.description'));
        $document->setMetaTag('keywords', $config->getSetting('site.keywords'));
        $document->setTitle($config->getSetting('site.title'));
    }// fn dispatch

    /**
     * Displays the site.
     *
     * @return void
     */
    public function render()
    {
        global $g_errorList;

        $errors = array();
        if (array_key_exists('controller', $GLOBALS)) {
            $errors = $GLOBALS['controller']->getRequest()->getParam('errors', null);
        }

        $uri = self::GetURIInstance();
        $document = self::GetHTMLDocumentInstance();

        $context = CampTemplate::singleton()->context();
        // sets the appropiate template if site is not in mode online
        if ($this->getSetting('site.online') == 'N') {
            $templates_dir = CS_TEMPLATES_DIR . DIR_SEP . CS_SYS_TEMPLATES_DIR;
            $template = '_campsite_offline.tpl';
        } elseif (!$uri->publication->defined) {
            $templates_dir = CS_TEMPLATES_DIR . DIR_SEP . CS_SYS_TEMPLATES_DIR;
            $template = '_campsite_error.tpl';
            $error_message = 'The site alias \'' . $_SERVER['HTTP_HOST']
                    . '\' was not assigned to a publication. Please create a publication and '
                    . ' assign it the current site alias.';
        } elseif (is_array($g_errorList) && !empty($g_errorList)) {
            $templates_dir = CS_TEMPLATES_DIR . DIR_SEP . CS_SYS_TEMPLATES_DIR;
            $template = '_campsite_error.tpl';
            $error_message = 'At initialization: ' . $g_errorList[0]->getMessage();
        } elseif (!empty($errors)) {
            $templates_dir = CS_TEMPLATES_DIR . DIR_SEP . CS_SYS_TEMPLATES_DIR;
            $template = '_campsite_error.tpl';
            if (defined('APPLICATION_ENV') && APPLICATION_ENV == 'development') {
                $error_message = $errors->exception;
            } else {
                $error_message = 'Error occured.';
            }
        } else {
            $template = $uri->getTemplate(CampRequest::GetVar(CampRequest::TEMPLATE_ID));
            switch ($template) {
                case null:
                    $error_message = "Unable to select a template! "
                    . "Please make sure the following conditions are met:\n"
                    . "<li>there is at least one issue published and it had assigned "
                    . "valid templates for the front, section and article pages;</li>\n"
                    . "<li>a template was assigned for the URL error handling in "
                    . "the publication configuration screen.";
                    $templates_dir = CS_TEMPLATES_DIR . DIR_SEP . CS_SYS_TEMPLATES_DIR;
                    $template = '_campsite_error.tpl';
                    break;
                default:
                    $themePath = $uri->getThemePath();
                    $templates_dir = CS_TEMPLATES_DIR . DIR_SEP . $themePath;
            }
        }
        $params = array(
            'context' => $context,
            'template' => $template,
            'templates_dir' => $templates_dir,
            'error_message' => isset($error_message) ? $error_message : null
        );
        $document->render($params);
    }// fn render

    /**
     * @param string $p_eventName
     */
    public function event($p_eventName)
    {
        $preview = CampTemplate::singleton()->context()->preview;
        switch ($p_eventName) {
            case 'beforeRender':
                return $preview ? CampRequest::GetVar('previewLang', null) : null;
            case 'afterRender':
                if ($preview) {
                    $errorList = '';
                    foreach ($GLOBALS['g_errorList'] as $error) {
                        $errorList = $errorList . '<p>' . addslashes($error->getMessage()) . '</p>';
                    }
                    ?>
                        <script>
                            parent.e.document.getElementById('error_count').innerHTML = '<?php echo(count($GLOBALS['g_errorList'])); ?>';
                            parent.e.document.getElementById('error_list').innerHTML = '<?php echo($errorList); ?>';
                        </script>
                    <?php
                }
                break;
        }
    }// fn event

    /**
     * Returns a CampConfig instance.
     *
     * @return object
     *      A CampConfig instance
     */
    public static function GetConfigInstance()
    {
        return CampConfig::singleton();
    }// fn GetConfig

    /**
     * Returns a CampHTMLDocument instance.
     *
     * @return object
     *      The CampHTMLDocument instance.
     */
    public static function GetHTMLDocumentInstance()
    {
        $config = self::GetConfigInstance();
        $attributes = array(
            'type' => CampRequest::GetVar('format', 'html'),
            'charset' => $config->getSetting('site.charset'),
            'language' => CampRequest::GetVar('language', 'en')
        );
        return CampHTMLDocument::singleton($attributes);
    }// fn GetHTMLDocumentInstance

    /**
     * Returns a CampSession instance.
     *
     * @return object
     *    A CampSession instance
     */
    public static function GetSessionInstance()
    {
        return CampSession::singleton();
    }// fn GetSession

    /**
     * Returns the appropiate URI instance.
     *
     * @param string $p_uri
     *      The URI to work with
     * @return CampURI
     */
    public static function GetURIInstance()
    {
        static $uriInstance = null;

        $alias = new Alias($_SERVER['HTTP_HOST']);
        if ($alias->exists()) {
            $publication = new Publication($alias->getPublicationId());
            $urlType = $publication->getUrlTypeId();
        }

        // sets url type to default if necessary
        if (!isset($urlType)) {
            $config = self::GetConfigInstance();
            $urlType = $config->getSetting('campsite.url_default_type');
        }

        // instanciates the corresponding URI object
        switch ($urlType) {
            case 1:
                $uriInstance = new CampURITemplatePath();
                break;
            case 2:
                $uriInstance = new CampURIShortNames();
                break;
        }

        return $uriInstance;
    } // fn GetURI

} // class CampSite
