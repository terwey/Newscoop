<?php
/**
 * @package Newscoop
 * @subpackage Languages
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Newscoop\Annotations\Acl;
use Newscoop\Entity\Language;

/**
 * @Acl(resource="language", action="manage")
 */
class Admin_LanguagesController extends Zend_Controller_Action
{
    /** @var Newscoop\Entity\Repository\LanguageRepository */
    private $repository= NULL;

    /** 
     * Init
     *
     * @return void
     */
    public function init()
    {
        camp_load_translation_strings('languages');

        $this->repository = $this->_helper->entity->getRepository('Newscoop\Entity\Language');
    }

    public function indexAction()
    {
        $this->view->languages = $this->repository->getLanguages();

        $this->view->actions = array(
            array(
                'label' => $this->translator->trans('Add new Language'),
                'module' => 'admin',
                'controller' => 'languages',
                'action' => 'add',
                'resource' => 'language',
                'privilege' => 'manage',
            ),
        );
    }

    public function addAction()
    {
        $this->_helper->acl->check('language', 'manage');

        $form = new Admin_Form_Language;
        $form->setMethod('post')->setAction('');

        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            try {
                $language = new Language;
                $this->repository->save($language, $form->getValues());
                $this->_helper->flashMessenger->addMessage($this->translator->trans('Language added.'));
                $this->_helper->redirector('index');
            } catch (Exception $e) {
                $form->getElement('name')->addError($this->translator->trans('Name taken.'));
            }
        }

        $this->view->form = $form;
    }

    public function editAction()
    {
        $language = $this->getLanguage();

        $form = new Admin_Form_Language;
        $form->setAction('')
            ->setMethod('post')
            ->setDefaultsFromEntity($language);

        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            try {
                $this->repository->save($language, $form->getValues());

                $this->_helper->flashMessenger->addMessage($this->translator->trans('Language saved.'));
                $this->_helper->redirector('edit', 'languages', 'admin', array('language' => $language->getId()));
            } catch (InvalidArgumentException $e) {
                $this->view->error = $e->getMessage();
            }
        }

        $this->view->language = $language;
        $this->view->form = $form;
    }

    /**
     * @Acl(action="delete")
     */
    public function deleteAction()
    {
        $this->_helper->acl->check('language', 'delete');

        $language = $this->getLanguage();
        if ($language->getCode() === 'en') {
            $this->_helper->flashMessenger->addMessage($this->translator->trans('English language cannot be removed.'));
            $this->_helper->redirector('index', 'languages', 'admin');
        }

        if ($this->repository->isUsed($language)) {
            $this->_helper->flashMessenger->addMessage($this->translator->trans('Language is in use and cannot be removed.'));
            $this->_helper->redirector('index', 'languages', 'admin');
        }

        Localizer::DeleteLanguageFiles($language->getCode());
        $this->repository->delete($language->getId());
        $this->_helper->flashMessenger->addMessage($this->translator->trans('Language removed.'));
        $this->_helper->redirector('index', 'languages', 'admin');
    }

    /**
     * Get language
     *
     * @return Newscoop\Entity\Language
     */
    private function getLanguage()
    {
        $id = (int) $this->getRequest()->getParam('language');
        if (!$id) {
            $this->_helper->flashMessenger(array('error', $this->translator->trans('Language id not specified')));
            $this->_helper->redirector('index');
        }

        $language = $this->repository->findOneBy(array('id' => $id));
        if (empty($language)) {
            $this->_helper->flashMessenger->addMessage($this->translator->trans('Language not found.'));
            $this->_helper->redirector('index');
        }

        return $language;
    }
}
