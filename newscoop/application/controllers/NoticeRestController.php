<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
use \Newscoop\Entity\Notice as Notice;

class NoticeRestController extends Zend_Rest_Controller
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
        /*$id = $this->getRequest()->getParam('id', null);
        if (isset($id)) {
            $noticeRecord = $this->noticeRepo->find($id);
            var_dump($noticeRecord->getTitle());
            $this->_helper->json($noticeRecord, true);
        }*/
    }

    public function putAction()
    {
    }

    public function postAction()
    {
    }
}
