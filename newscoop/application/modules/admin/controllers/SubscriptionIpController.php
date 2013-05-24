<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Newscoop\Annotations\Acl;
use Newscoop\Entity\User\Ip;
use Newscoop\Entity\User\Subscriber;

/**
 * @Acl(resource="subscription", action="manage")
 */
class Admin_SubscriptionIpController extends Zend_Controller_Action
{
    public function init()
    {
        camp_load_translation_strings('api');
        camp_load_translation_strings('users');
        camp_load_translation_strings('user_subscriptions');
    }

    public function indexAction()
    {
        $subscriber = $this->_helper->entity('Newscoop\Entity\User\Subscriber', 'user');

        $this->view->subscriber = $subscriber;
        $this->view->actions = array(
            array(
                /** @Desc("Add new IP address") */
                'label' => 'ip.address.add.new',
                'module' => 'admin',
                'controller' => 'subscription-ip',
                'action' => 'add',
                'reset_params' => false,
                'class' => 'add',
            ),
        );
    }

    public function addAction()
    {
        $subscriber = $this->_helper->entity('Newscoop\Entity\User\Subscriber', 'user');

        $form = $this->createForm();
        $form->setAction('')->setMethod('post');

        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            $ip = new Ip;
            $repository = $this->_helper->entity->getRepository($ip);

            try {
                $repository->save($ip, $subscriber, $form->getValues());
                $this->_helper->entity->flushManager();

                $this->_helper->flashMessenger($this->translator->transChoice('IP Address $1', $this->translator->trans('saved')));
                $this->_helper->redirector('edit', 'subscriber', 'admin', array(
                    'user' => $subscriber->getId(),
                ));
            } catch (PDOException $e) {
                $form->getElement('ip')->addError($this->translator->trans('IP Address added allready'));
            }
        }

        $this->view->form = $form;
    }

    public function deleteAction()
    {
        $subscriber = $this->_helper->entity(new Subscriber, 'user');
        $repository = $this->_helper->entity->getRepository(new Ip);
        $ip = $this->_getParam('ip', '');

        $repository->delete($ip, $subscriber);
        $this->_helper->entity->flushManager();

        $this->_helper->flashMessenger($this->translator->trans('IP Address $1', $this->translator->trans('deleted')));
        $this->_helper->redirector('edit', 'subscriber', 'admin', array(
            'user' => $subscriber->getId(),
        ));
    }

    public function createForm()
    {
        $form = new Zend_Form;

        $form->addElement('text', 'ip', array(
            /** @Desc("Start IP") */
            'label' => 'ip.address.start',
            'required' => true,
            'validators' => array(
                array('notEmpty', true, array(
                    'messages' => array(
                        /** @Desc("Value is required and can't be empty") */
                        Zend_Validate_NotEmpty::IS_EMPTY => $this->translator->trans("error.value.required.isempty"),
                    ),
                )),
                array('ip', true, array(
                    'messages' => array(
                        Zend_Validate_Ip::NOT_IP_ADDRESS => $this->translator->transChoice("'%value%' is not a valid IP Address"),
                    ),
                )),
            ),
        ));

        $form->addElement('text', 'number', array(
            /** @Desc("Number of addresses") */
            'label' => 'ip.address.amount',
            'required' => true,
            'validators' => array(
                array('notEmpty', true, array(
                    'messages' => array(
                        Zend_Validate_NotEmpty::IS_EMPTY => $this->translator->trans("Value is required and can't be empty"),
                    ),
                )),
                array('greaterThan', true, array(
                    0,
                    'messages' => array(
                        Zend_Validate_GreaterThan::NOT_GREATER => $this->translator->trans("'%value%' must be greater than '%min%'"),
                    ),
                )),
            ),
        ));

        $form->addElement('submit', 'submit', array(
            /** @Desc("Save") */
            'label' => 'ip.address.save',
        ));

        return $form;
    }
}
