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

    public function init()
    {
        ini_set('memory_limit', '3072M');

        $this->service = $this->_helper->service('notice');
        $this->em = $this->_helper->service('em');
        $this->tagManager = $this->_helper->service('tag.manager');

        $this->getHelper('contextSwitch')->addActionContext('index', 'json')->initContext();
        $this->getHelper('contextSwitch')->addActionContext('get', 'json')->initContext();

    }

    public function headAction()
    {
    }

    public function indexAction()
    {

        $query = $this->getRequest()->getParam('query', null);
        if (isset($query)) {
            $queryParts = explode('/', $query);
        } else {
            $queryParts = array();
        }
        $noticeCollection = $this->service->findAll(2, $queryParts);

        $this->_helper->json($noticeCollection, true);
    }

    public function getAction()
    {
        /*
        $tagRepo = $this->em->getRepository('DoctrineExtensions\\Taggable\\Entity\\Tag');

        // find all article ids matching a particular query
        //$ids = $tagRepo->getResourceIdsForTag('notice', 'footag');

        $tags = $tagRepo->getTagsWithCountArray('');
        foreach ($tags as $name => $count) {
            $cloud[$name] = $count;
        }

        */
        $id = $this->getRequest()->getParam('id', null);
        if(isset($id)){

            $noticeRecord = $this->service->find($id);
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

            $newNotice = new \Newscoop\Entity\Notice();
            $newNotice->setTitle($form->title->getValue());
            $newNotice->setBody($form->body->getValue());
            $newNotice->setFirstname($form->firstname->getValue());
            $newNotice->setLastname($form->lastname->getValue());

            $dateTime = new DateTime('now');
            $newNotice->setPublished($dateTime);
            $newNotice->setStatus('saved');

            $tagNames = $this->tagManager->splitTagNames($form->tags->getValue());
            $tags = $this->tagManager->loadOrCreateTags($tagNames);

            $this->em->persist($newNotice);
            $this->em->flush();

            // Add a list of tags on your taggable resource..
            $this->tagManager->addTags($tags, $newNotice);
            $this->tagManager->saveTagging($newNotice);


            $this->_helper->flashMessenger("Notice created");
            $this->_helper->redirector->gotoUrl('/admin/notice');
        }


        exit;
    }

    public function deleteAction()
    {
        list($user, $ip) = explode(':', $this->_getParam('id'));
        $this->_helper->service('subscription.ip')->delete(array(
            'user' => $user,
            'ip'   => $ip,
        ));
        $this->_helper->json(array());
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
