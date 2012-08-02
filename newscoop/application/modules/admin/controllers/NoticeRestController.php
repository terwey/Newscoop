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

        //$this->getHelper('contextSwitch')->addActionContext('index', 'json')->initContext();
        //$this->getHelper('contextSwitch')->addActionContext('get', 'json')->initContext();

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

        $noticeRepo = $this->em->getRepository('Newscoop\\Entity\\Notice');

        $noticeCollection = $noticeRepo->findAll(2, $queryParts);
        $this->_helper->json($noticeCollection, true);
    }

    public function getAction()
    {
        exit('mehz');
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

            if($form->id->getValue()){
                $noticeRepo = $this->em->getRepository('Newscoop\\Entity\\Notice');
                $noticeRecord = $noticeRepo->find($form->id->getValue());
            }else{
                $noticeRecord =  new \Newscoop\Entity\Notice();
            }
            $noticeRecord->setTitle($form->title->getValue());
            $noticeRecord->setBody($form->body->getValue());
            $noticeRecord->setFirstname($form->firstname->getValue());
            $noticeRecord->setLastname($form->lastname->getValue());

            $dateTime = new DateTime($form->published->getValue());
            $noticeRecord->setPublished($dateTime);
            $noticeRecord->setStatus('saved');

            $catIds = explode(',',$form->categories->getValue());
            $catRepo = $this->em->getRepository('Newscoop\\Entity\\NoticeCategory');

            foreach($catIds as $id){
                if($cat = $catRepo->find($id))
                    $categories[] = $cat;
            }
            if(count($categories)){
                $noticeRecord->setCategories($categories);
            }

            $this->em->persist($noticeRecord);
            $this->em->flush();

            // Add a list of tags on your taggable resource..
            //$this->tagManager->addTags($tags, $newNotice);
            //$this->tagManager->saveTagging($newNotice);


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
