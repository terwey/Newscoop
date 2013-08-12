<?php
/**
 * @package Campware
 */

/**
 * Since the XML_Serializer package is not yet stable,
 * we must use our own package.  The package has a bug fix applied
 * that is required for the Localizer XML files to work.
 */
require_once('XML/Serializer.php');
require_once('XML/Unserializer.php');

global $g_localizerConfig;

// The default language, which forms the keys for
// all other languages.
$g_localizerConfig['DEFAULT_LANGUAGE'] = 'en';

// Filename prefix for translation files.
$g_localizerConfig['FILENAME_PREFIX'] = 'locals';

// Filename prefix for the global translation file -
// a file that is always loaded with the particular
// locals file.
$g_localizerConfig['FILENAME_PREFIX_GLOBAL'] = 'globals';

// Set to a specific type if your code is using that type.
// Currently supported types are 'gs' and 'xml'.
// You can also set this to the empty string and the code
// will do its best to figure out the current type.
$g_localizerConfig['DEFAULT_FILE_TYPE'] = 'gs';

// The top-level directory to the set of directories
// that need translation files.
$g_localizerConfig['BASE_DIR'] = $GLOBALS['g_campsiteDir'];

// The top-level directory to the set of directories
// that need translation files.
$g_localizerConfig['TRANSLATION_DIR'] = $GLOBALS['g_campsiteDir'].'/admin-files/lang';

// Name of the XML file that contains the list of supported languages.
$g_localizerConfig['LANGUAGE_METADATA_FILENAME'] = 'languages.xml';

// File encoding for XML files.
$g_localizerConfig['FILE_ENCODING'] = 'UTF-8';

// For the interface - the relative path of the icons directory
global $Campsite;
$g_localizerConfig['ICONS_DIR'] = $Campsite['ADMIN_IMAGE_BASE_URL'];

// The size of the input fields for the admin interface.
$g_localizerConfig['INPUT_SIZE'] = 70;

// List supported file types, in order of preference.
$g_localizerConfig['FILE_TYPES'] = array('xml', 'gs');

$g_localizerConfig['LOADED_FILES'] = array();

// Map of prefixes to directory names.
$mapPrefixToDir = array(
    '' => null,
    'globals' => null,
    'home' => array(
        '/admin-files/',
        '/application/layouts/scripts',
        '/application/modules/admin/Bootstrap.php',
        '/application/modules/admin/views/helpers',
        '/application/modules/admin/views/scripts',
    ),
    'api' => '/classes/*',
    'library' => '/admin-files/libs/*',
    'pub' => '/admin-files/pub',
    'issues' => '/admin-files/issues',
    'sections' => '/admin-files/sections',
    'articles' => array(
        '/admin-files/articles',
        '/application/modules/admin/controllers/PlaylistController.php',
        '/application/modules/admin/controllers/BlogController.php',
        '/application/modules/admin/controllers/MultidateController.php',
        '/application/modules/admin/views/scripts/playlist',
        '/application/modules/admin/views/scripts/blog',
        '/application/modules/admin/views/scripts/blog',
    ),
    'article_images' => array(
        '/admin-files/articles/images',
        '/application/modules/admin/controllers/ImageController.php',
        '/application/modules/admin/controllers/SlideshowController.php',
        '/application/modules/admin/controllers/MediaController.php',
        '/application/modules/admin/controllers/RenditionController.php',
        '/application/modules/admin/views/scripts/image',
        '/application/modules/admin/views/scripts/slideshow',
        '/application/modules/admin/views/scripts/media',
        '/application/modules/admin/views/scripts/rendition',
        '/application/modules/admin/forms/Slideshow.php',
        '/application/modules/admin/forms/SlideshowCreate.php',
        '/application/modules/admin/forms/SlideshowItem.php',
    ),
    'article_files' => '/admin-files/articles/files',
    'article_topics' => '/admin-files/articles/topics',
    'article_comments' => '/admin-files/articles/comments',
    'media_archive' => '/admin-files/media-archive',
    'geolocation' => '/admin-files/articles/locations',
    'comments' => array(
        '/admin-files/comments',
        '/application/modules/admin/views/scripts/comment',
        '/application/modules/admin/views/scripts/comment-commenter',
        '/application/modules/admin/views/scripts/comment-acceptance',
        '/application/modules/admin/views/scripts/feedback',
        '/application/modules/admin/forms/Ban.php',
        '/application/modules/admin/forms/Comment.php',
        '/application/modules/admin/forms/Commenter.php',
        '/application/modules/admin/forms/CommentAcceptance.php',
        '/application/modules/admin/forms/Comment',
        '/application/modules/admin/controllers/CommentController.php',
        '/application/modules/admin/controllers/CommentAcceptanceController.php',
        '/application/modules/admin/controllers/CommentCommenterController.php',
        '/application/modules/admin/controllers/FeedbackController.php',
    ),
    'system_pref' => '/admin-files/system_pref',
    'themes' => array(
        '/application/modules/admin/views/scripts/themes',
        '/application/modules/admin/views/scripts/template',
        '/application/modules/admin/forms/Template.php',
        '/application/modules/admin/forms/Theme.php',
        '/application/modules/admin/forms/Theme',
        '/application/modules/admin/forms/Upload.php',
        '/application/modules/admin/forms/ReplaceTemplate.php',
        '/application/modules/admin/controllers/TemplateController.php',
        '/application/modules/admin/controllers/ThemesController.php',
    ),
    'article_types' => '/admin-files/article_types',
    'article_type_fields' => '/admin-files/article_types/fields',
    'topics' => '/admin-files/topics',
    'languages' => array(
        '/admin-files/languages',
        '/application/modules/admin/controllers/LanguagesController.php',
        '/application/modules/admin/views/scripts/languages',
        '/application/modules/admin/forms/Language.php',
    ),
    'country' => '/admin-files/country',
    'localizer' => '/admin-files/localizer',
    'logs' => array(
        '/admin-files/logs',
        '/application/modules/admin/controllers/LogController.php',
        '/application/modules/admin/views/scripts/log',
    ),
    'users' => array(
        '/admin-files/users',
        '/application/modules/admin/controllers/UserController.php',
        '/application/modules/admin/controllers/AuthController.php',
        '/application/controllers/RegisterController.php',
        '/application/modules/admin/views/scripts/user',
        '/application/modules/admin/views/scripts/auth',
        '/application/modules/admin/forms/User.php',
        '/application/modules/admin/forms/Profile.php',
        '/application/forms/Register.php',
        '/application/forms/Profile.php',
        '/application/forms/Confirm.php',
    ),
    'user_subscriptions' => array(
        '/admin-files/users/subscriptions',
        '/application/modules/admin/controllers/SubscriptionController.php',
        '/application/modules/admin/controllers/SubscriberController.php',
        '/application/modules/admin/controllers/SubscriptionIpController.php',
        '/application/modules/admin/views/scripts/subscription',
        '/application/modules/admin/views/scripts/subscriber',
        '/application/modules/admin/views/scripts/subscription-ip',
        '/application/modules/admin/forms/Subscriber.php',
        '/application/modules/admin/forms/Subscription.php',
    ),
    'user_subscription_sections' => array(
        '/admin-files/users/subscriptions/sections',
        '/application/modules/admin/controllers/SubscriptionSectionController.php',
        '/application/modules/admin/views/scripts/subscription-section',
        '/application/modules/admin/forms/Subscription',
    ),
    'user_types' => array(
        '/admin-files/user_types',
        '/application/modules/admin/controllers/UserGroupController.php',
        '/application/modules/admin/controllers/AclController.php',
        '/application/modules/admin/views/scripts/user-group',
        '/application/modules/admin/views/scripts/acl',
        '/application/controllers/helpers/Acl.php',
        '/application/plugins/Acl.php',
    ),
    'bug_reporting' => array(
        '/admin-files/bugreporter',
        '/application/controllers/ErrorController.php',
        '/application/views/scripts/error',
    ),
    'feedback' => array(
        '/admin-files/feedback',
        '/application/modules/admin/views/scripts/feedback/',
    ),
    'preview' => '/template_engine/classes',
    'tiny_media_plugin' => '/js/tinymce/plugins/campsitemedia',
    'plugins' => '/admin-files/plugins',
    'extensions' => '/extensions/*',
    'authors' => '/admin-files/users/authors_ajax',
    'help' => array(
        '/application/modules/admin/controllers/ApplicationController.php',
        '/application/modules/admin/views/scripts/application',
    ),
    'support' => array(
        '/application/modules/admin/controllers/SupportController.php',
        '/application/modules/admin/views/scripts/support',
    ),
);

foreach (CampPlugin::GetPluginsInfo(true) as $info) {
	if (array_key_exists('localizer', $info) && is_array($info['localizer'])) {
		$mapPrefixToDir[$info['localizer']['id']] = $info['localizer']['path'];
	}
}

$g_localizerConfig['MAP_PREFIX_TO_DIR'] = $mapPrefixToDir;
unset($mapPrefixToDir);

?>
