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
class Admin_NoticeRestController extends Zend_Rest_Controller
{
    private $em;
    private $noticeRepo;

    public function init()
    {
        $this->em = $this->_helper->service('em');
        $this->noticeRepo = $this->em->getRepository('Newscoop\\Entity\\Notice');
    }

    public function headAction()
    {
    }

    public function deleteAction()
    {
    }

    public function indexAction()
    {
        $query = $this->getRequest()->getParam('q', null);

        if (isset($query) && !empty($query)) {
            $queryParts = explode('/', $query);
        } else {
            $queryParts = array();
        }

        $noticeCollection = $this->noticeRepo->getNotices(2, $queryParts);
        $result = array('status' => 'ok', 'data' => array('notices' => $noticeCollection));

        $this->_helper->json($result, true);
    }

    public function getAction()
    {
        $id = $this->getRequest()->getParam('id', null);
        if (isset($id)) {
            $noticeRecord = $this->noticeRepo->find($id);
            var_dump($noticeRecord->getTitle());
            $this->_helper->json($noticeRecord, true);
        }
    }

    public function putAction()
    {
    }

    public function postAction()
    {

        $request = $this->getRequest();
        $form = new \Admin_Form_NoticeItem();


        if ($request->isPost() && $form->isValid($request->getPost())) {

            if ($form->id->getValue()) {
                $noticeRecord = $this->noticeRepo->find($form->id->getValue());
            } else {
                $noticeRecord = new \Newscoop\Entity\Notice();
            }
            $noticeRecord->setTitle($form->title->getValue());
            $noticeRecord->setBody($form->body->getValue());
            $noticeRecord->setFirstname($form->firstname->getValue());
            $noticeRecord->setLastname($form->lastname->getValue());

            $dateTimePub = new DateTime($form->published->getValue());
            $noticeRecord->setPublished($dateTimePub);

            $dateTimeUnpub = new DateTime($form->unpublished->getValue());
            $noticeRecord->setUnpublished($dateTimeUnpub);

            $noticeRecord->setStatus('saved');

            $catIds = explode(',', $form->categories->getValue());
            $categories = array();

            if("" !== $catIds[0]){
                $catRepo = $this->em->getRepository('Newscoop\\Entity\\NoticeCategory');
                foreach ($catIds as $id) {
                    if ($cat = $catRepo->find($id))
                        $categories[] = $cat;
                }
                $noticeRecord->setCategories($categories);
            }

            $this->em->persist($noticeRecord);
            $this->em->flush();

            $this->_helper->flashMessenger("Notice created");
            $this->_helper->redirector->gotoUrl('/admin/notice');
        }
    }


    /**
     * Get values
     *
     * @return array
     */
    private function getValues()
    {
        $values = json_decode($this->getRequest()->getRawBody(), true);
        return $values;
    }
}
