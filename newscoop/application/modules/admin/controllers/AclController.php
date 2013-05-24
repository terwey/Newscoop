<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Newscoop\Annotations\Acl;
use Newscoop\Entity\Acl\Role;
use Newscoop\Entity\Acl\Rule;
use Newscoop\Entity\User;

/**
 * @Acl(ignore="1")
 */
class Admin_AclController extends Zend_Controller_Action
{
    /** @var Resource_Acl */
    private $acl;

    /** @var Doctrine\ORM\EntityRepository */
    private $ruleRepository;

    /** @var string */
    private $resource;

    public function init()
    {
        camp_load_translation_strings('user_types');

        $this->ruleRepository = $this->_helper->entity->getRepository('Newscoop\Entity\Acl\Rule');

        $this->ruleTypes = array(
            'allow' => $this->translator->trans('Allow'),
            'deny' => $this->translator->trans('Deny'),
        );

        $this->groups = array(
            'authoring' => $this->translator->trans('Authoring'),
            'structure' => $this->translator->trans('Structure'),
            'layout' => $this->translator->trans('Layout'),
            'users' => $this->translator->trans('Users'),
            'system' => $this->translator->trans('System'),
            'plugins' => $this->translator->trans('Plugins'),
        );

        $this->resources = array(
            'authoring' => array(
                'article' => $this->translator->trans('Articles'),
                'image' => $this->translator->trans('Images'),
                'comment' => $this->translator->trans('Comments'),
                'feedback' => $this->translator->trans('Feedback Messages'),
                'file' => $this->translator->trans('Files'),
                'editor' => $this->translator->trans('Rich-Text Editor Preferences'),
            ),
            'structure' => array(
                'publication' => $this->translator->trans('Publications'),
                'issue' => $this->translator->trans('Issues'),
                'section' => $this->translator->trans('Sections'),
                'topic' => $this->translator->trans('Topics'),
                'language' => $this->translator->trans('Languages'),
                'playlist' => $this->translator->trans('Article Playlists')
            ),
            'users' => array(
                'user-group' => $this->translator->trans('User Groups'),
                'user' => $this->translator->trans('Staff'),
                'author' => $this->translator->trans('Authors'),
                'subscriber' => $this->translator->trans('Subscribers'),
                'subscription' => $this->translator->trans('Subscriptions'),
            ),
            'layout' => array(
                'theme' => $this->translator->trans('Themes'),
                'template' => $this->translator->trans('Templates'),
                'article-type' => $this->translator->trans('Article Types'),
            ),
            'system' => array(
                'system-preferences' => $this->translator->trans('Global'),
                'indexer' => $this->translator->trans('Search Indexer'),
                'country' => $this->translator->trans('Countries'),
                'log' => $this->translator->trans('Log'),
                'localizer' => $this->translator->trans('Localizer'),
                'backup' => $this->translator->trans('Backup'),
                'cache' => $this->translator->trans('Cache'),
                'notification' => $this->translator->trans('Notification'),
            ),
            'plugins' => array(
                'plugin' => $this->translator->trans('Plugins'),
                'pluginpoll' => $this->translator->trans('Polls'),
                'plugin-recaptcha' => $this->translator->trans('ReCaptcha'),
                'plugin-soundcloud' => $this->translator->trans('Soundcloud'),
            ),
        );

        // i18n
        $this->actions = array(
            'add' => $this->translator->trans('add'),
            'admin' => $this->translator->trans('admin'),
            'attach' => $this->translator->trans('attach'),
            'clear' => $this->translator->trans('clear'),
            'delete' => $this->translator->trans('delete'),
            'edit' => $this->translator->trans('edit'),
            'enable' => $this->translator->trans('enable'),
            'get' => $this->translator->trans('get'),
            'guest' => $this->translator->trans('guest'),
            'manage' => $this->translator->trans('manage'),
            'moderate' => $this->translator->trans('moderate'),
            'moderate-comment' => $this->translator->trans('moderate'),
            'moderator' => $this->translator->trans('moderate'),
            'move' => $this->translator->trans('move'),
            'notify' => $this->translator->trans('notify'),
            'publish' => $this->translator->trans('publish'),
            'translate' => $this->translator->trans('translate'),
            'view' => $this->translator->trans('view'),

            // editor related
            'bold' => $this->translator->trans('bold'),
            'charactermap' => $this->translator->trans('character map'),
            'copycutpaste' => $this->translator->trans('copy/cut/paste'),
            'enlarge' => $this->translator->trans('enlarge'),
            'findreplace' => $this->translator->trans('find/replace'),
            'fontcolor' => $this->translator->trans('font color'),
            'fontface' => $this->translator->trans('font face'),
            'fontsize' => $this->translator->trans('font size'),
            'horizontalrule' => $this->translator->trans('horizontal rule'),
            'image' => $this->translator->trans('image'),
            'indent' => $this->translator->trans('indent'),
            'italic' => $this->translator->trans('italic'),
            'link' => $this->translator->trans('link'),
            'listbullet' => $this->translator->trans('list bullet'),
            'listnumber' => $this->translator->trans('list number'),
            'sourceview' => $this->translator->trans('source view'),
            'spellcheckerenabled' => $this->translator->trans('spell checker enabled'),
            'statusbar' => $this->translator->trans('statusbar'),
            'strikethrough' => $this->translator->trans('strikethrough'),
            'subhead' => $this->translator->trans('subhead'),
            'subscript' => $this->translator->trans('subscript'),
            'superscript' => $this->translator->trans('superscript'),
            'table' => $this->translator->trans('table'),
            'textalignment' => $this->translator->trans('text alignment'),
            'textdirection' => $this->translator->trans('text direction'),
            'underline' => $this->translator->trans('underline'),
            'undoredo' => $this->translator->trans('undo/redo'),
        );

        $this->_helper->contextSwitch()
            ->addActionContext('edit', 'json')
            ->initContext();

        $this->acl = Zend_Registry::get('acl');

        $this->resource = $this->_getParam('user', false) ? 'user' : 'user-group';
    }

    public function editAction()
    {
        $role = $this->_getParam('user', false)
            ? $this->_helper->entity->find('Newscoop\Entity\User', $this->_getParam('user'))
            : $this->_helper->entity->find('Newscoop\Entity\User\Group', $this->_getParam('group'));

        if ($this->getRequest()->isPost()) {
            $values = $this->getRequest()->getPost();
            if ($this->isBlocker($values)) {
                $this->view->error = $this->translator->trans("You can't deny yourself to manage $1", $this->getResourceName($this->resource));
                return;
            }

            try {
                $this->ruleRepository->save($values, $this->_getParam('user', false));
            } catch (\Exception $e) {
                $this->view->error = $e->getMessage();
            }

            return;
        }

        $this->view->role = $role;
        $this->view->groups = $this->groups;
        $this->view->resources = $this->resources;
        $this->view->actions = $this->acl->getResources();
        $this->view->actionNames = $this->actions;
        $this->view->acl = $this->getHelper('acl')->getAcl($role);
    }

    /**
     * Test if adding rule would block current user to manage users/types
     *
     * @param array $values
     * @return bool
     */
    private function isBlocker(array $values)
    {
        $user = Zend_Registry::get('user');
        $acl = $this->_helper->acl->getAcl($user);

        if (in_array($values['role'], $acl->getRoles()) && $values['type'] == 'deny') {
            $resource = empty($values['resource']) ? null : $values['resource'];
            $action = empty($values['action']) ? null : $values['action'];
            $acl->deny($values['role'], $resource, $action);

            return !$acl->isAllowed($user, $this->resource, 'manage');
        }

        return False;
    }

    /**
     * Get translated resource name
     *
     * @param string $resource
     * @return string
     */
    private function getResourceName($resource)
    {
        foreach ($this->resources as $resources) {
            if (isset($resources[$resource])) {
                return $resources[$resource];
            }
        }

        return $resource;
    }
}
