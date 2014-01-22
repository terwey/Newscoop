<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Newscoop\Annotations\Acl;
use Newscoop\Entity\Subscription;
use Newscoop\Entity\SubscriptionSection;
use Newscoop\Entity\User\Subscriber;

/**
 * @Acl(resource="subscription", action="manage")
 */
class Admin_SubscriptionSectionController extends Zend_Controller_Action
{
    /** @var Newscoop\Entity\Repository\SubscriptionSectionRepository */
    private $repository;

    public function init()
    {
        $this->repository = $this->_helper->entity->getRepository('Newscoop\Entity\SubscriptionSection');

        $sections = array();
        $sectionRepository = $this->_helper->entity->getRepository('Newscoop\Entity\Section');
        foreach ($sectionRepository->findAll() as $section) {
            if (!isset($sections[$section->getNumber()])) {
                $sections[$section->getNumber()] = $section->getName();
            }
        }

        $this->view->sections = $sections;
    }

    public function indexAction()
    {   
        $translator = \Zend_Registry::get('container')->getService('translator');
        $subscription = $this->_helper->entity->get('Newscoop\Entity\Subscription', 'subscription');
        $this->view->subscription = $subscription;

        $this->view->actions = array(
            array(
                'label' => $translator->trans('Add new section', array(), 'user_subscription_sections'),
                'module' => 'admin',
                'controller' => 'subscription-section',
                'action' => 'add',
                'resource' => 'subscription',
                'privilege' => 'manage',
                'reset_params' => FALSE,
                'class' => 'add',
            ),
            array(
                'label' => $translator->trans('Edit all sections', array(), 'user_subscription_sections'),
                'module' => 'admin',
                'controller' => 'subscription-section',
                'action' => 'edit-all',
                'resource' => 'subscription',
                'privilege' => 'manage',
                'reset_params' => FALSE,
            ),
        );
    }

    public function addAction()
    {   
        $translator = \Zend_Registry::get('container')->getService('translator');
        $form = new Admin_Form_Subscription_SectionAddForm;
        $form->setAction('')->setMethod('post');

        // get form section options
        $subscription = $this->_helper->entity->get('Newscoop\Entity\Subscription', 'subscription');
        $publication = $subscription->getPublication();
        $sectionRepository = $this->_helper->entity->getRepository('Newscoop\Entity\Section');

        $individualSections = $sectionRepository->getAvailableSections($publication, $subscription);
        $allSections = $sectionRepository->getAvailableSections($publication, $subscription, TRUE);

        $form->getElement('sections_select')->setMultioptions($this->getOptions($individualSections, TRUE));
        $form->getElement('sections_all')->setMultioptions($this->getOptions($allSections));

        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            $subscriptionRepository = $this->_helper->entity->getRepository($subscription);
            try {
                $subscriptionRepository->addSections($subscription, $form->getValues());
                $this->_helper->entity->flushManager();

                $this->_helper->flashMessenger($translator->trans('Section $1', array('$1' => $translator->trans('saved', array(), 'user_subscription_sections')), 'user_subscription_sections'));
                $this->_helper->redirector('index', 'subscription-section', 'admin', array(
                    'user' => $this->_getParam('user'),
                    'subscription' => $this->_getParam('subscription'),
                ));
            } catch (\InvalidArgumentException $e) {
                $this->_helper->flashMessenger(array('error', $translator->trans('Select sections for subscribing', array(), 'user_subscription_sections')));
                $this->_helper->redirector('add', 'subscription-section', 'admin', array(
                    'user' => $this->_getParam('user'),
                    'subscription' => $this->_getParam('subscription'),
                ));
            }
        }

        $this->view->form = $form;
    }

    public function editAction()
    {   
        $translator = \Zend_Registry::get('container')->getService('translator');
        $section = $this->_helper->entity->get('Newscoop\Entity\SubscriptionSection', 'section');
        $form = new Admin_Form_Subscription_SectionEditForm;
        $form->setAction('')->setMethod('post');
        $form->setDefaults(array(
            'name' => $this->view->sections[$section->getSectionNumber()],
            'language' => $section->getLanguageName(),
            'start_date' => $section->getStartDate()->format('Y-m-d'),
            'days' => $section->getDays(),
            'paid_days' => $section->getPaidDays(),
        ));

        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            $this->repository->save($section, $form->getValues());
            $this->_helper->entity->flushManager();

            $this->_helper->flashMessenger($translator->trans('Section $1', array('$1' => $translator->trans('saved', array(), 'user_subscription_sections')), 'user_subscription_sections'));
            $this->_helper->redirector('index', 'subscription-section', 'admin', array(
                'user' => $this->_getParam('user'),
                'subscription' => $this->_getParam('subscription'),
            ));
        }

        $this->view->form = $form;
    }

    public function editAllAction()
    {   
        $translator = \Zend_Registry::get('container')->getService('translator');
        $subscription = $this->_helper->entity->get('Newscoop\Entity\Subscription', 'subscription');
        $sections = $subscription->getSections();
        if (empty($sections)) {
            $this->_helper->flashMessenger($translator->trans('No sections to edit', array(), 'user_subscription_sections'));
            $this->_helper->redirector('index', 'subscription-section', 'admin', array(
                'user' => $this->_getParam('user'),
                'subscription' => $this->_getParam('subscription'),
            ));
        }

        $section = $sections[0];

        $form = new Admin_Form_Subscription_SectionEditForm;
        $form->setAction('')->setMethod('post');
        $form->setDefaults(array(
            'name' => $translator->trans('All'),
            'language' => $translator->trans('N/A'),
            'start_date' => $section->getStartDate()->format('Y-m-d'),
            'days' => $section->getDays(),
            'paid_days' => $section->getPaidDays(),
        ));

        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            foreach ($sections as $section) {
                $this->repository->save($section, $form->getValues());
            }
            $this->_helper->entity->flushManager();

            $this->_helper->flashMessenger($translator->trans('Sections saved', array(), 'user_subscription_sections'));
            $this->_helper->redirector('index', 'subscription-section', 'admin', array(
                'user' => $this->_getParam('user'),
                'subscription' => $this->_getParam('subscription'),
            ));
        }

        $this->view->form = $form;
    }

    public function deleteAction()
    {   
        $translator = \Zend_Registry::get('container')->getService('translator');
        $section = $this->_helper->entity->get('Newscoop\Entity\SubscriptionSection', 'section');
        $this->repository->delete($section);
        $this->_helper->entity->flushManager();

        $this->_helper->flashMessenger($translator->trans('Section $1', array('$1' => $translator->trans('deleted', array(), 'user_subscription_sections')), 'user_subscription_sections'));
        $this->_helper->redirector('index', 'subscription-section', 'admin', array(
            'user' => $this->_getParam('user'),
            'subscription' => $this->_getParam('subscription'),
        ));
    }

    private function getOptions(array $sections, $language = FALSE)
    {   
        $translator = \Zend_Registry::get('container')->getService('translator');
        $options = array();
        foreach ($sections as $section) {
            $key = $section->getNumber() . ($language ? '_' . $section->getLanguageId() : '');
            $options[$key] = $section->getNumber() . ' - ' . $section->getName();
            if ($language) {
                $options[$key] .= ' (' . $section->getLanguageName() . ')';
            }
        }

        return $options;
    }
}
