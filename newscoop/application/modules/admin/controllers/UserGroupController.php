<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Newscoop\Entity\User\Group;

/**
 * @Acl(action="manage")
 */
class Admin_UserGroupController extends Zend_Controller_Action
{
    /** @var Newscoop\Entity\Repository\User\GroupRepository */
    private $repository;

    public function init()
    {
        camp_load_translation_strings('user_types');

        $this->repository = $this->_helper->entity->getRepository('Newscoop\Entity\User\Group');
    }

    public function indexAction()
    {
        $this->view->groups = $this->repository->findAll();

        $this->_helper->sidebar(array(
            'label' => getGS('Add new user type'),
            'controller' => 'user-group',
            'action' => 'edit-name',
        ));
    }

    public function editNameAction()
    {
        $form = new Admin_Form_UserGroup;
        $form->setAction($this->view->url(array(
            'action' => 'edit-name',
        )))->setMethod('post');

        try {
            $group = $this->_helper->entity('Newscoop\Entity\User\Group', 'group');
            $form->setDefaults(array(
                'name' => $group->getName(),
            ));
        } catch (InvalidArgumentException $e) {
            $group = new Group;
        }

        $request = $this->getRequest();
        if ($request->isPost() && $form->isValid($request->getPost())) {
            try {
                $this->repository->save($group, $form->getValues());
                $this->_helper->entity->flushManager();

                $this->_helper->flashMessenger(getGS('User type saved.'));
            } catch (Exception $e) {
                $this->_helper->flashMessenger(getGS('That type name already exists, please choose a different name.'));
            }

            $this->_helper->redirector($this->_getParam('group') ? 'edit' : 'index', 'user-group', 'admin', array(
                'group' => $this->_getParam('group'),
            ));
        }

        $this->view->form = $form;
    }

    public function editAction()
    {
        $group = $this->_helper->entity(new Group, 'group');
        $this->view->group = $group;
    }

    public function deleteAction()
    {
        $groupId = $this->getRequest()->getParam('group', 0);
        $group = $this->repository->find($groupId);
        $users = $group->getUsers();
        
        if (count($users) == 0) {
            $this->repository->delete($groupId);
            $this->_helper->flashMessenger->addMessage(getGS('User type deleted.'));
        }
        else {
            $this->_helper->flashMessenger->addMessage(getGS('Can not delete a user type with assigned users.'));
        }
        $this->_helper->redirector('index');
    }
}
