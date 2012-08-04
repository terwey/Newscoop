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

        $trees = array();
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
        $categoryForm = new Admin_Form_NoticeCategory();
        $categoryArray = $this->getAllCategoriesArray();
        $availableParentModules = array();
        foreach ($categoryArray as $category) {
            $availableParentModules[$category['id']] = str_repeat('-', $category['lvl']) . $category['title'];
        }
        $categoryForm->setCategories($availableParentModules);
        $categoryForm->setAction($this->view->baseUrl('admin/notice/category-add'));
        $this->view->categoryForm = $categoryForm;

    }

    public function categoryAddAction()
    {
        $request = $this->getRequest();

        $categoryForm = new Admin_Form_NoticeCategory();
        $categoriesArray = $this->getAllCategoriesArray();

        if (count($categoriesArray)) {
            $availableParentCategories = array();
            foreach ($categoriesArray as $module) {
                $availableParentCategories[$module['id']] = str_repeat('-', $module['lvl']) . $module['title'];
            }
            $categoryForm->setCategories($availableParentCategories);
        }

        if ($request->isPost() && $categoryForm->isValid($request->getPost())) {


            $categoryRecord = new \Newscoop\Entity\NoticeCategory();
            $categoryRecord->setTitle($categoryForm->title->getValue());

            if ('' !== $categoryForm->parent->getValue()) {
                $categoryRepo = $this->em->getRepository('Newscoop\Entity\NoticeCategory');

                $parentCategory = $categoryRepo->findOneById($categoryForm->parent->getValue());
                $categoryRecord->setParent($parentCategory);
            }

            $this->em->persist($categoryRecord);
            $this->em->flush();

            $this->_helper->redirector->gotoUrl('/admin/notice/category');
        } else {
            $this->_helper->json(array('status' => 'error', 'errors' => $categoryForm->getErrors()));
        }

    }

    private function getAllCategoriesArray()
    {
        // create query to fetch tree nodes
        $query = $this->em
            ->createQueryBuilder()
            ->select('category')
            ->from('Newscoop\Entity\NoticeCategory', 'category')
            ->orderBy('category.root, category.lft', 'ASC')
            ->getQuery();

        return $query->getArrayResult();
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
        $table->setCols(
            array('id' => $view->toggleCheckbox(),
                'lastname' => getGS('Name'),
                'title' => getGS('Notice'),
                'categories' => getGS('Categories'),
                'created' => getGS('Creation date'),
                'published' => getGS('Publish date'),
                'status' => getGS('Status'),
                'actions' => 'Actions'),
            array('id' => false,'actions' => false));

        //$table->setSorting(array('published' => 'desc'));
        $table->setInitialSorting(array('id' => 'desc'));

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

            return array(
                'index' => $index++,
                'can' => array('enable' => $acl['enable'], 'edit' => $acl['edit']),
                'user' =>
                array(
                    'firstname' => $notice->getFirstname(),
                    'lastname' => $notice->getLastname(),
                ),
                'notice' => array(
                    'id' => $notice->getId(),
                    'created' => array('date' => $notice->getCreated()->format('Y-m-d')),
                    'published' => array('date' => $notice->getPublished()->format('Y-m-d')),
                    'unpublished' => array('date' => $notice->getUnpublished()->format('Y-m-d')),
                    'title' => $view->escape($notice->getTitle()),
                    'body' => $view->escape($notice->getBody()),
                    'likes' => '',
                    'dislikes' => '',
                    'categories' => implode(',', $categoriesArray),
                    'status' => $notice->getStatusName(),
                    'recommended' => 'meeh',
                    'action' => array('update' => $view->url(
                        array('action' => 'update', 'format' => 'json')),
                        'reply' => $view->url(
                            array('action' => 'reply', 'format' => 'json')))),
                'thread' => array('name' => $view->escape('meeh'),
                    'link' => array
                    ('edit' => $view->baseUrl("admin/notice/edit/id/") . $notice->getId(),
                        'get' => $view->baseUrl("admin/articles/get.php?") . 'meeh')));
        });

        $table->setOption('fnDrawCallback', 'datatableCallback.draw')
            ->setOption('fnRowCallback', 'datatableCallback.row')
            ->setOption('fnServerData', 'datatableCallback.addServerData')
            ->setOption('fnInitComplete', 'datatableCallback.init')
            ->setOption('sDom', '<"top">lf<"#actionExtender">rit<"bottom"ip>')
            ->setStripClasses()
            ->toggleAutomaticWidth(false)
            ->setDataProp(
            array('id' => null,
                'lastname' => null,
                'title' => null,
                'categories' => null,
                'created' => null,
                'published' => null,
                'status' => null,
                'actions' => null))
            //->setVisible(array('actions' => false))
            ->setClasses(
            array('id' => 'noticeId',
                'lastname' => 'noticeUser',
                'title'=> 'noticeTitle',
                'created' => 'noticeCategories',
                'created' => 'noticeCreated',
                'published' => 'noticePublished',
                'status' => 'noticeStatus',
                'actions' => 'noticeActions'));
        $table->dispatch();

    }

    public function index2Action()
    {
        $notices = $this->noticeRepo->findAll(1);

        $this->view->noticeCollection = $notices;
        $this->view->noticeForm = new Admin_Form_NoticeItem();
    }


    public function editAction()
    {
        $noticeId = $this->getRequest()->getParam('id', null);
        $repo = $this->em->getRepository('Newscoop\Entity\Notice');
        $noticeForm = new Admin_Form_NoticeItem();
        $noticeForm->setAction($this->view->baseUrl('admin/notice-rest'));

        $notice = $repo->find($noticeId);
        if (!$notice) {
            $notice = new \Newscoop\Entity\Notice();
        } else {
            $noticeForm->setDefaultsFromEntity($notice);
        }

        $repo = $this->em->getRepository('Newscoop\Entity\NoticeCategory');
        $rootNodes = $this->view->categoryCollection = $repo->getRootNodes();

        $trees = array();
        foreach ($rootNodes as $node) {
            $treeArray = $repo->childrenHierarchy($node);
            $trees[$node->getSlug()] = array('root' => $node, 'children' => $treeArray);
        }
        $this->view->trees = $trees;

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

        $status_enum = array_flip(Notice::$status_enum);

        try {
            foreach ($notices as $id) {
                $notice = $repo->find($id);
                $msg = getGS('Notice "$2" status was changed to $3 by $1', Zend_Registry::get('user')->getName(),
                    $notice->getTitle(), $status);
                $this->_helper->flashMessenger($msg);
                $notice->setStatus($status_enum[$status]);
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
