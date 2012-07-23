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
    protected $tagManager;

    public function init()
    {
        $this->service = $this->_helper->service('notice');
        $this->em = $this->_helper->service('em');
        $this->tagManager = $this->_helper->service('tag.manager');

        $this->getHelper('contextSwitch')->addActionContext('get', 'json')->initContext();

    }

    public function categoryAction()
    {

        $repo = $this->em->getRepository('Newscoop\Entity\NoticeCategory');
        $rootNodes = $this->view->categoryCollection = $repo->getRootNodes();

        foreach ($rootNodes as $node) {

            $htmlTree = $repo->childrenHierarchy(
                $node, /* starting from root nodes */
                false, /* load all children, not only direct */
                true, /* render html */
                array(
                    'decorate'            => true,
                    'representationField' => 'title'
                )
            );
            $trees[$node->getSlug()] = array('root' => $node, 'children' => $htmlTree);
        }

        $this->view->Trees = $trees;
        /*
                $food = new \Newscoop\Entity\NoticeCategory();
                $food->setTitle('Food');

                $fruits = new \Newscoop\Entity\NoticeCategory();
                $fruits->setTitle('Fruits');
                $fruits->setParent($food);

                $vegetables = new \Newscoop\Entity\NoticeCategory();
                $vegetables->setTitle('Vegetables');
                $vegetables->setParent($food);

                $carrots = new \Newscoop\Entity\NoticeCategory();
                $carrots->setTitle('Carrots');
                $carrots->setParent($vegetables);

                $this->em->persist($food);
                $this->em->persist($fruits);
                $this->em->persist($vegetables);
                $this->em->persist($carrots);
                $this->em->flush();
                exit;
        */
    }

    public function indexAction()
    {

        $notices = $this->service->findAll(1);

        foreach ($notices as $notice) {
            $test = $this->tagManager->loadTagging($notice);
        }

        $this->view->noticeCollection = $notices;
        $this->view->noticeForm = new Admin_Form_NoticeItem();

        //$tool = new \Doctrine\ORM\Tools\SchemaTool($this->em);


        /*$classes = array(
            $this->em->getClassMetadata('Newscoop\Entity\Notice')
        );

        $result = $tool->updateSchema($classes,false);
        \Zend_Debug::dump($result);
        exit();*/
    }

    public function editAction()
    {
        $noticeId = $this->getRequest()->getParam('id');
        $notice = $this->service->find($noticeId);
        //print_r($notice->getTags()->toArray());
        $noticeForm = new Admin_Form_NoticeItem();

        $noticeForm->setDefaultsFromEntity($notice);
        $this->view->noticeForm = $noticeForm;
        $this->view->notice = $notice;
        $this->tagManager->loadTagging($notice);

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

    public function deletecatAction(){
        $repo = $this->em->getRepository('Newscoop\Entity\NoticeCategory');
        $treeToRemove = $repo->find(9);
        $this->em->remove($treeToRemove);
        $this->em->flush();
        exit;
    }
}
