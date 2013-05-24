<?php
/**
 * @package Newscoop
 * @subpackage Subscriptions
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Newscoop\Annotations\Acl;
use Newscoop\Entity\Comment\Commenter;

/**
 * @Acl(resource="comment", action="moderate")
 */
class Admin_CommentCommenterController extends Zend_Controller_Action
{

    /**
     * @var ICommenter
     */
    private $commenterRepository;

    /**
     * @var IAcceptance
     */
    private $acceptanceRepository;

    /**
     * @var IArticle
     */
    private $articleRepository;

    /**
     * @var Newscoop\Entity\Repository\CommentRepository
     */
    private $commentRepository;

    /**
     * @var IPublication
     */
    private $publicationRepository;

    /**
     *
     * @var Admin_Form_Commenter
     */
    private $form;


    public function init()
    {
        camp_load_translation_strings('comments');

        // get commenter repository
        $this->commenterRepository = $this->_helper->entity->getRepository('Newscoop\Entity\Comment\Commenter');

        // get acceptance repository
        $this->acceptanceRepository = $this->_helper->entity->getRepository('Newscoop\Entity\Comment\Acceptance');

        // get article repository
        $this->articleRepository = $this->_helper->entity->getRepository('Newscoop\Entity\Article');

        // get publication repository
        $this->publicationRepository = $this->_helper->entity->getRepository('Newscoop\Entity\Publication');

        // get comment repository
        $this->commentRepository = $this->_helper->entity->getRepository('Newscoop\Entity\Comment');

        $this->getHelper('contextSwitch')->addActionContext('index', 'json')->initContext();
        // set the default form for comment commenter and set method to post
        $this->form = new Admin_Form_Commenter;
        $this->form->setMethod('post');
        return $this;

    }

    public function indexAction()
    {
        $this->getHelper('contextSwitch')->addActionContext('index', 'json')->initContext();
        $table = $this->getHelper('datatable');

        $table->setDataSource($this->commenterRepository);
        $table->setOption('oLanguage', array('oPaginate' => array(
                'sFirst' => $this->translator->trans('First'),
                'sLast' => $this->translator->trans('Last'),
                'sNext' => $this->translator->trans('Next'),
                'sPrevious' => $this->translator->trans('Previous'),
            ),
            'sZeroRecords' => $this->translator->trans('No records found.'),
            'sSearch' => $this->translator->trans('Search'),
            'sInfo' => $this->translator->trans('Showing _START_ to _END_ of _TOTAL_ entries'),
            'sEmpty' => $this->translator->trans('No entries to show'),
            'sInfoFiltered' => $this->translator->trans(' - filtering from _MAX_ records'),
            'sLengthMenu' => $this->translator->trans('Display _MENU_ records'),
            'sInfoEmpty' => '')
        );
        $table->setCols(
            array('time_created' => $this->translator->trans('Date Created'), 'name' => $this->translator->trans('Name'), 'user' => $this->translator->trans('Username'),
                 'email' => $this->translator->trans('Email'), 'url' => $this->translator->trans('Website'), 'ip' => $this->translator->trans('Ip'), 'edit' => $this->translator->trans('Edit'),
                 'ban' => $this->translator->trans('Ban'), 'delete' => $this->translator->trans('Delete')));

        $view = $this->view;
        $table->setHandle(function($commenter) use ($view)
            {
                $urlParam = array('commenter' => $commenter->getId());
                return array($commenter->getTimeCreated()->format('Y-i-d H:i:s'), $commenter->getName(),
                             $commenter->getUsername(), $commenter->getEmail(), $commenter->getUrl(),
                             $commenter->getIp(), $view->linkEdit($urlParam), $view->linkBan($urlParam),
                             $view->linkDelete($urlParam),);
            });

        $table->dispatch();
    }

    /**
     * Action for Adding a Commenter
     */
    public function addAction()
    {
        $commenter = new Commenter;

        $this->handleForm($this->form, $commenter);

        $this->view->form = $this->form;
        $this->view->commenter = $commenter;
    }

    /**
     * Action for Editing a Commenter
     */
    public function editAction()
    {
        $params = $this->getRequest()->getParams();
        if (!isset($params['commenter'])) {
            throw new InvalidArgumentException;
        }
        $commenter = $this->commenterRepository->find($params['commenter']);
        if ($commenter) {
            $this->form->setFromEntity($commenter);
            $this->handleForm($this->form, $commenter);
            $this->view->form = $this->form;
            $this->view->commenter = $commenter;
        }
    }

    /**
     * Action for Deleteing a Commenter
     */
    public function deleteAction()
    {
        $params = $this->getRequest()->getParams();
        if (!isset($params['commenter'])) {
            throw new InvalidArgumentException;
        }
        $commenter = $this->commenterRepository->find($params['commenter']);
        if ($commenter) {
            $this->commenterRepository->delete($commenter);
            $this->commenterRepository->flush();
            $this->_helper->flashMessenger($this->translator->trans('Commenter "$1" deleted.', $commenter->getName()));
            $this->_helper->redirector->gotoSimple('index');
        }
    }

    public function toggleBanAction()
    {
        $params = $this->getRequest()->getParams();
        if (!isset($params['commenter']) && (!isset($params['article']) || !isset($params['forum']))) {
            throw new InvalidArgumentException;
        }
        if(isset($params['thread']) && isset($params['language']))
            $publication = $this->articleRepository->find(array('number' => $params['thread'], 'language' => $params['language']))->getPublication();
        if(isset($params['forum']))
            $publication = $this->publicationRepository->find($params['forum']);
            
        //$publication = null;
        $commenter = $this->commenterRepository->find($params['commenter']);

        $form = new Admin_Form_Ban;
        $this->handleBanForm($form, $commenter, $publication);
        $form->setValues($commenter, $this->acceptanceRepository->isBanned($commenter, $publication));
        $this->view->form = $form;
    }

    /**
     * Method for saving a banned
     *
     * @param ZendForm $p_form
     * @param Newscoop\Entity\Comment\Commenter $p_commenter
     */
    private function handleBanForm(Admin_Form_Ban $p_form, $p_commenter, $p_publication)
    {
        if ($this->getRequest()->isPost() && $p_form->isValid($_POST)) {
            if ($p_form->getSubmit()->isChecked()) {
                $values = $p_form->getValues();
                $this->acceptanceRepository->saveBanned($p_commenter, $p_publication, $values);
                $this->acceptanceRepository->flush();
                $this->_helper->flashMessenger($this->translator->trans('Ban for commenter "$1" saved.', $p_commenter->getName()));
                if ($p_form->getDeleteComments()->isChecked()) {
                    $this->commenterRepository->delete($p_commenter, $p_form->getValues());
                    $this->commenterRepository->flush();
                }
            }
            $this->_helper->redirector->gotoSimple('index', 'comment');
        }
    }

    /**
     * Method for saving a commenter
     *
     * @param ZendForm $p_form
     * @param ICommenter $p_commenter
     */
    private function handleForm(Zend_Form $p_form, $p_commenter)
    {
        if ($this->getRequest()->isPost() && $p_form->isValid($_POST)) {
            $values = $p_form->getValues();
            $values['ip'] = $this->getRequest()->getClientIp();
            $values['time_created'] = new DateTime;
            $this->commenterRepository->save($p_commenter, $values);
            $this->commenterRepository->flush();
            $this->_helper->flashMessenger($this->translator->trans('Commenter "$1" saved.', $p_commenter->getName()));
            $this->_helper->redirector->gotoSimple('index');
        }
    }

}
