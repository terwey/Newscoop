<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Newscoop\Entity\Acl\Role,
    Newscoop\Entity\Acl\Rule,
    Newscoop\Entity\User\Staff;

/**
 * @Acl(ignore="1")
 */
class Admin_AclController extends Zend_Controller_Action
{
    /** @var Resource_Acl */
    private $acl;

    /** @var array */
    private $ruleTypes;

    /** @var Newscoop\Entity\Repository\Acl\RuleRepository */
    private $ruleRepository;

    public function init()
    {
        camp_load_translation_strings('user_types');

        $this->ruleRepository = $this->_helper->entity->getRepository('Newscoop\Entity\Acl\Rule');

        $this->ruleTypes = array(
            'allow' => getGS('Allow'),
            'deny' => getGS('Deny'),
        );

        $this->acl = Zend_Registry::get('acl');

        $this->_helper->contextSwitch()
            ->addActionContext('get-resource-actions', 'json')
            ->initContext();
    }

    public function listAction()
    {
        $role = $this->_helper->entity->get('Newscoop\Entity\Acl\Role', 'role');

        // get resources
        $resources = array('' => getGS('Global'));
        $rules = $inheritedRules = array('' => array());
        foreach (array_keys($this->acl->getResources()) as $resource) {
            $resources[$resource] = $this->formatName($resource);

            // init arrays
            $rules[$resource] = $inheritedRules[$resource] = array();
        }

        // get rules
        foreach ($role->getRules() as $rule) {
            $resource = $rule->getResource();
            $rules[$resource][] = (object) array(
                'id' => $rule->getId(),
                'class' => $rule->getType(),
                'type' => $this->ruleTypes[$rule->getType()],
                'action' => $this->formatName($rule->getAction()),
            );
        }

        try { // get inherited rules
            $staff = $this->_helper->entity->get('Newscoop\Entity\User\Staff', 'user');
            foreach ($staff->getGroups() as $group) {
                foreach ($group->getRoleRules() as $rule) {
                    $resource = $rule->getResource();
                    $inheritedRules[$resource][] = (object) array(
                        'id' => $rule->getId(),
                        'class' => $rule->getType(),
                        'type' => $this->ruleTypes[$rule->getType()],
                        'action' => $this->formatName($rule->getAction()),
                    );
                }
            }
        } catch (InvalidArgumentException $e) { // ignore
        }

        $this->view->role = $role;
        $this->view->resources = $resources;
        $this->view->rules = $rules;
        $this->view->rulesParents = $inheritedRules;
        $this->view->readonly = $this->_getParam('readonly');
    }

    public function editAction()
    {
        $form = new Admin_Form_Acl;
        $form->setAction($this->view->url(array(
            'controller' => 'acl',
            'action' => 'edit',
        )))->setMethod('post');

        // add resources
        foreach (array_keys($this->acl->getResources()) as $resource) {
            $form->resource->addMultiOption($resource, $this->formatName($resource));
        }

        // add actions
        foreach ($this->acl->getActions() as $action) {
            $form->action->addMultiOption($action, $this->formatName($action));
        }

        // add types
        $form->type->setMultiOptions($this->ruleTypes);

        $form->setDefaults(array(
            'type' => 'allow',
            'role' => $this->_getParam('role', 0),
            'next' => $this->_getParam('next'),
        ));

        $request = $this->getRequest();
        if ($request->isPost() && $form->isValid($request->getPost())) {
            try {
                $rule = new Rule();
                $this->ruleRepository->save($rule, $form->getValues());
                $this->_helper->entity->flushManager();

                $this->_helper->flashMessenger(getGS('Rule saved.'));
            } catch (Exception $e) {
                $form->_helper->flashMessenger(getGS('Rule for this resource/action exists already.'));
            }

            $this->_redirect($this->_getParam('next'), array(
                'prependBase' => false,
            ));
        }

        $this->view->form = $form;
    }

    /**
     * @Acl(resource="user", action="manage")
     */
    public function deleteAction()
    {
        $this->ruleRepository->delete($this->_getParam('rule'));
        $this->_helper->entity->flushManager();

        $this->_helper->flashMessenger->addMessage(getGS('Rule removed.'));
        $this->_redirect(urldecode($this->_getParam('next')), array(
            'prependBase' => false,
        ));
    }

    public function getResourceActionsAction()
    {
        $actions = array();
        $resource = $this->_getParam('resource');
        if (!empty($resource)) {
            $actions = $this->acl->getActions($resource);
        }

        $this->view->actions = $actions;
    }

    /**
     * Format name
     *
     * @param string $name
     * @return string
     */
    private function formatName($name)
    {
        $parts = explode('-', $name);
        $parts = array_map('ucfirst', $parts);
        return implode(' ', $parts);
    }
}
