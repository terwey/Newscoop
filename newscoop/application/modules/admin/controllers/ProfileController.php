<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Newscoop\Entity\User\Staff;

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
    }

    public function indexAction()
    {
        $this->view->placeholder('title')
            ->set(getGS('My account'));

        $this->_forward('edit', 'staff', 'admin', array(
            'user' => $this->staff->getId(),
        ));
    }

    public function accessAction()
    {
        $this->view->placeholder('title')
            ->set(getGS('View access'));

        $this->_forward('edit', 'acl', 'admin', array(
            'user' => $this->staff->getId(),
        ));
    }
}
