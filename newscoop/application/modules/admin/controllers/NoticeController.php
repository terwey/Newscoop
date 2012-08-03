<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
use \Newscoop\Entity\Notice as Notice;

/**
 * @Acl(resource="notice", action="manage")
 */
class Admin_NoticeController extends Zend_Controller_Action
{
    private $em;

    private $noticeRepo;

    public function init()
    {
        $this->em = $this->_helper->service('em');
        // get notice repository
        $this->noticeRepo = $repo = $this->em->getRepository('Newscoop\Entity\Notice');

        camp_load_translation_strings('notices');
        // get language repository
        $this->languageRepository = $this->_helper->entity->getRepository('Newscoop\Entity\Language');

        return $this;


    }

    public function categoryAction()
    {
        //$this->getHelper('contextSwitch')->addActionContext('get', 'json')->initContext();

        $repo = $this->em->getRepository('Newscoop\Entity\NoticeCategory');
        $rootNodes = $this->view->categoryCollection = $repo->getRootNodes();

        foreach ($rootNodes as $node) {

            $htmlTree = $repo->childrenHierarchy(
                $node, /* starting from root nodes */
                false, /* load all children, not only direct */
                true, /* render html */
                array(
                    'decorate' => true,
                    'representationField' => 'title'
                )
            );
            $trees[$node->getSlug()] = array('root' => $node, 'children' => $htmlTree);
        }

        $this->view->Trees = $trees;
        /*
        $locations = new \Newscoop\Entity\NoticeCategory();
        $locations->setTitle('Ort');

        $city0 = new \Newscoop\Entity\NoticeCategory();
        $city0->setTitle('Basel');
        $city0->setParent($locations);

        $city1 = new \Newscoop\Entity\NoticeCategory();
        $city1->setTitle('Wiehl');
        $city1->setParent($locations);

        $city3 = new \Newscoop\Entity\NoticeCategory();
        $city3->setTitle('Zürich');
        $city3->setParent($city1);

        $this->em->persist($locations);
        $this->em->persist($city0);
        $this->em->persist($city1);
        $this->em->persist($city3);
        $this->em->flush();
        exit;
        */

    }

    public function indexAction()
    {
        $this->_forward('table');
    }

    /**
     * Action to make the table
     */
    public function tableAction()
    {
        $this->getHelper('contextSwitch')->addActionContext('table', 'json')->initContext();
        $view = $this->view;
        $table = $this->getHelper('Datatable');
        /* @var $table Action_Helper_Datatable */
        $table->setDataSource($this->noticeRepo);
        $table->setOption('oLanguage', array('sSearch' => ''));
        $table->setCols(array('index' => $view->toggleCheckbox(), 'user' => getGS('Name'),
            'notice' => getGS('Date') . ' / ' . getGS('Notice'), 'status' => getGS('Status'),
            'actions' => 'Actions'), array('index' => false,'actions' => false));

        $table->setInitialSorting(array('notice' => 'desc'));

        $index = 1;
        $acl = array();
        $acl['edit'] = $this->_helper->acl->isAllowed('comment', 'edit');
        $acl['enable'] = $this->_helper->acl->isAllowed('comment', 'enable');

        $table->setHandle(function($notice) use ($view, $acl, &$index)
        {
            $categories = $notice->getCategories();
            $categoriesArray = array();
            foreach ($categories as $category) {
                $categoriesArray[] = $category->getTitle();
            }

            return array('index' => $index++, 'can' => array('enable' => $acl['enable'], 'edit' => $acl['edit']),
                'user' =>
                array(
                    'firstname' => $notice->getFirstname(),
                    'lastname' => $notice->getLastname(),
                    'usernameEncoded' => urlencode('empt_placehoölder'),
                    'email' => 'empt_placehoölder',
                    'avatar' => (string)$view->getAvatar('mail@tail.de', array('img_size' => 50,
                        'default_img' => 'wavatar')),
                    'ip' => 'meeh', 'url' => 'meeh',
                    'banurl' => $view->url(
                        array('controller' => 'comment-commenter', 'action' => 'toggle-ban',
                            'user' => 'meeh', 'thread' => 'meeh', 'language' => 2))),
                'notice' => array(
                    'id' => $notice->getId(),
                    'created' => array('date' => 'meeh',
                        'time' => 'meeh'),
                    'subject' => 'meeh',
                    'title' => $view->escape($notice->getTitle()),
                    'body' => $view->escape($notice->getBody()),
                    'likes' => '',
                    'dislikes' => '',
                    'categories' => implode(',',$categoriesArray),
                    'status' => $notice->getStatus(),
                    'recommended' => 'meeh',
                    'action' => array('update' => $view->url(
                        array('action' => 'update', 'format' => 'json')),
                        'reply' => $view->url(
                            array('action' => 'reply', 'format' => 'json')))),
                'thread' => array('name' => $view->escape('meeh'),
                    'link' => array
                    ('edit' => $view->baseUrl("admin/notice/edit/id/") . $notice->getId(),
                        'get' => $view->baseUrl("admin/articles/get.php?") . 'meeh'),
                    'forum' => array('name' => $view->escape('meeh')),
                    'section' => array('name' => ($section) ? $view->escape($section->getName()) : null)),);
        });

        $table->setOption('fnDrawCallback', 'datatableCallback.draw')
            ->setOption('fnRowCallback', 'datatableCallback.row')
            ->setOption('fnServerData', 'datatableCallback.addServerData')
            ->setOption('fnInitComplete', 'datatableCallback.init')
            ->setOption('sDom', '<"top">lf<"#actionExtender">rit<"bottom"ip>')
            ->setStripClasses()
            ->toggleAutomaticWidth(false)
            ->setDataProp(
            array('index' => null, 'user' => null, 'notice' => null, 'status' => null,
                'actions' => null))
            ->setVisible(array('index' => false))
            ->setClasses(
            array('index' => 'noticeId', 'user' => 'noticeUser', 'notice' => 'noticeTimeCreated',
                'status' => 'noticeStatus','actions' => 'noticeActionsHolder'));
        $table->dispatch();
    }

    public function index2Action()
    {
        $notices = $this->service->findAll(1);

        $this->view->noticeCollection = $notices;
        $this->view->noticeForm = new Admin_Form_NoticeItem();
    }

    public function editAction()
    {
        $noticeId = $this->getRequest()->getParam('id',null);
        $repo = $this->em->getRepository('Newscoop\Entity\Notice');

        $notice = $repo->find($noticeId);
        if(!$notice){
            $notice = new \Newscoop\Entity\Notice();
        }
        $noticeForm = new Admin_Form_NoticeItem();

        $repo = $this->em->getRepository('Newscoop\Entity\NoticeCategory');


        $rootNodes = $this->view->categoryCollection = $repo->getRootNodes();

        foreach ($rootNodes as $node) {
            $treeArray = $repo->childrenHierarchy($node);
            $trees[$node->getSlug()] = array('root' => $node, 'children' => $treeArray);
        }

        $this->view->trees = $trees;

        $noticeForm->setDefaultsFromEntity($notice);
        $this->view->noticeForm = $noticeForm;
        $this->view->notice = $notice;
    }

    public function deleteAction()
    {
        $noticeId = $this->getRequest()->getParam('id');

        $notice = $this->service->find($noticeId);

        $this->em->remove($notice);
        $this->em->flush();

        $this->_helper->flashMessenger("Notice {$noticeId} deleted");
        $this->_helper->redirector->gotoUrl('/admin/notice');

    }


    public function deletecatAction()
    {
        $repo = $this->em->getRepository('Newscoop\Entity\NoticeCategory');
        $treeToRemove = $repo->find(9);
        $this->em->remove($treeToRemove);
        $this->em->flush();
        exit;
    }

    /**
     * Action for setting a status
     */
    public function setStatusAction()
    {
        $this->getHelper('contextSwitch')->addActionContext('set-status', 'json')->initContext();
        if (!SecurityToken::isValid()) {
            $this->view->status = 401;
            $this->view->message = getGS('Invalid security token!');
            return;
        }

        $status = $this->getRequest()->getParam('status');
        $notices = $this->getRequest()->getParam('notice');

        if (!is_array($notices)) {
            $notices = array($notices);
        }


        $repo = $this->em->getRepository('Newscoop\Entity\Notice');

        try {
            foreach ($notices as $id) {
                $notice = $repo->find($id);
                $msg = getGS('Notice "$2" status was changed to $3 by $1', Zend_Registry::get('user')->getName(),
                    $notice->getTitle(), $status);
                $this->_helper->flashMessenger($msg);
                $notice->setStatus($status);
                $this->em->persist($notice);
            }
            $this->em->flush();
        } catch (Exception $e) {
            $this->view->status = $e->getCode();
            $this->view->message = $e->getMessage();
            return;
        }
        $this->view->status = 200;
        $this->view->message = "succcesful";
    }
}
