<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Newscoop\Annotations\Acl;
use Newscoop\Entity\Subscription;
use Newscoop\Entity\User\Subscriber;

/**
 * @Acl(action="manage")
 */
class Admin_SubscriptionController extends Zend_Controller_Action
{
    public function init()
    {
        camp_load_translation_strings('api');
        camp_load_translation_strings('users');
        camp_load_translation_strings('user_subscriptions');

        $currency = new Zend_Currency('en_US');
        Zend_Registry::set('Zend_Currency', $currency);

        $this->_helper->layout->setLayout('modal');
    }

    public function indexAction()
    {
        $this->view->user = $this->_getParam('user');
        $this->view->subscriptions = $this->_helper->service('subscription')->findByUser($this->_getParam('user'));
        $this->view->publications = $this->_helper->service('content.publication')->findAll();
        $this->view->ips = $this->_helper->service('subscription.ip')->findByUser($this->_getParam('user'));
    }

    public function editAction()
    {
        $subscription = $this->_helper->service('subscription')->find($this->_getParam('subscription'));
        $sections = array();
        foreach ($subscription->getPublication()->getIssues() as $issue) {
            foreach ($issue->getSections() as $section) {
                $id = implode(':', array($section->getNumber(), $issue->getLanguage()->getId()));
                if (array_key_exists($id, $sections)) {
                    continue;
                }

                $sections[$id] = array(
                    'number' => $section->getNumber(),
                    'name' => $section->getName(),
                    'language' => array(
                        'id' => $issue->getLanguage()->getId(),
                        'name' => $issue->getLanguage()->getName(),
                    ),
                );
            }
        }

        $this->view->sections = array_values($sections);
        $this->view->subscription = $subscription;
        $this->view->subscriber = $this->_getParam('user');
    }

    public function addAction()
    {
        $subscriber = $this->_helper->entity->get('Newscoop\Entity\User\Subscriber', 'user');

        $publications = $this->_helper->entity->getRepository('Newscoop\Entity\Publication')->getSubscriberOptions($subscriber);
        if (empty($publications)) {
            $this->_helper->flashMessenger($this->translator->trans('Subscriptions exist for all available publications.'));
            $this->redirect();
        }

        $form = new Admin_Form_Subscription(array(
            'publications' => $publications,
            'languages' => $this->getLanguages(),
        ));

        $form->setMethod('post')->setAction('');

        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            $subscription = new Subscription;
            $repository = $this->_helper->entity->getRepository($subscription);
            $repository->save($subscription, $subscriber, $form->getValues());
            $this->_helper->entity->flushManager();

            $this->_helper->flashMessenger($this->translator->trans('Subscription $1', $this->translator->trans('saved')));
            $this->redirect();
        }

        $this->view->form = $form;
    }

    public function _editAction()
    {
        $subscription = $this->_helper->entity->get('Newscoop\Entity\Subscription', 'subscription');

        $form = $this->getToPayForm();
        $form->setAction('')->setMethod('post');

        $form->setDefaults(array(
            'to_pay' => sprintf('%.2f', $subscription->getToPay()),
        ));

        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            $em = $this->_helper->entity->getManager();
            $values = $form->getValues();
            $subscription->setToPay($values['to_pay']);
            $this->_helper->entity->flushManager();

            $this->_helper->flashMessenger($this->translator->trans('Subscription $1', $this->translator->trans('saved')));
            $this->redirect();
        }

        $this->view->form = $form;
    }

    public function toggleAction()
    {
        $em = $this->_helper->entity->getManager();

        $subscription = $this->_helper->entity->get('Newscoop\Entity\Subscription', 'subscription');
        $subscription->setActive(!$subscription->isActive());
        $em->flush();

        $this->_helper->flashMessenger($this->translator->trans('Subscription $1', $subscription->isActive() ? $this->translator->trans('activated') : $this->translator->trans('deactivated')));
        $this->redirect();
    }

    public function deleteAction()
    {
        $subscription = $this->_helper->entity->get('Newscoop\Entity\Subscription', 'subscription');
        $this->_helper->entity->getRepository($subscription)
            ->delete($subscription);
        $this->_helper->entity->flushManager();

        $this->_helper->flashMessenger($this->translator->trans('Subscription $1', $this->translator->trans('removed')));
        $this->redirect();
    }

    /**
     * Get languages
     *
     * @return array
     */
    private function getLanguages()
    {
        $repository = $this->_helper->entity->getRepository('Newscoop\Entity\Publication');
        $publications = $repository->findAll();

        if (empty($publications)) {
            return array();
        }

        $publication = $publications[0];

        $langs = array();
        foreach ($publication->getLanguages() as $lang) {
            $langs[$lang->getId()] = $lang->getName();
        }

        return $langs;
    }

    /**
     * Get to pay form
     *
     * @return Zend_Form
     */
    private function getToPayForm()
    {
        $form = new Zend_Form;

        $form->addElement('text', 'to_pay', array(
            'label' => $this->translator->trans('Left to pay'),
            'required' => true,
        ));

        $form->addElement('submit', 'submit', array(
            'label' => $this->translator->trans('Save'),
        ));

        return $form;
    }

    /**
     * Redirect after action
     *
     * @return void
     */
    public function redirect()
    {
        $action = 'index';
        $controller = $this->_getParam('controller');

        $next = $this->_getParam('next');
        if ($next) {
            list($controller, $action) = explode(':', $next);
        }

        $this->_helper->redirector($action, $controller, 'admin', array(
            'user' => $this->_getParam('user', 0),
        ));
    }
}
