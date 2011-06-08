<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * @Acl(ignore="1")
 */
class Admin_ProfileController extends Zend_Controller_Action
{
    /** @var Newscoop\Entity\User\Staff */
    private $staff;

    public function init()
    {
        $auth = Zend_Auth::getInstance();
        $this->staff = $this->_helper->entity->find('Newscoop\Entity\User\Staff', $auth->getIdentity());
        $this->view->staff = $this->staff;
    }

    public function indexAction()
    {
        $this->view->actions = array(array(
            'label' => getGS('Permissions'),
            'module' => 'admin',
            'controller' => 'profile',
            'action' => 'permissions',
        ));
    }

    public function permissionsAction()
    {
    }
}
