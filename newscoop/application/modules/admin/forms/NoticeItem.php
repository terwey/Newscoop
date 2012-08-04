<?php
/**
 * @package Newscoop
 * @copyright 2012 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 */
class Admin_Form_NoticeItem extends Zend_Form
{
    /**
     */
    public function init()
    {
        $this->addElement('hidden', 'id');

        $this->addElement('hidden', 'categories', array(
            'label' => getGS('Categories'),
        ));

        $this->addElement('text', 'title', array(
            'label' => getGS('TITLE'),
        ));

        $this->addElement('text', 'firstname', array(
            'label' => getGS('First name'),
        ));

        $this->addElement('text', 'lastname', array(
            'label' => getGS('Last name'),
        ));

        $this->addElement('textarea', 'body', array(
            'label' => getGS('CONTENT'),
        ));

        $this->addElement('text', 'published', array(
            'label' => getGS('Date of publication'),
            'required' => true
        ));

        $this->addElement('text', 'unpublished', array(
            'label' => getGS('Date of depublication'),
            'required' => true
        ));

        $this->addElement('submit', 'submit', array(
            'label' => getGS('save'),
        ));


    }

    /**
     * Set default for given entity
     *
     * @param Newscoop\Package\Item $item
     * @return Admin_Form_SlideshowItem
     */
    public function setDefaultsFromEntity(\Newscoop\Entity\Notice $notice)
    {
        $categories = $notice->getCategories();
        $cats = array();
        if($categories){
            foreach($categories as $cat){
                $cats[] = $cat->getId();
            }
        }

        $this->setDefaults(array(
            'id' => $notice->getId(),
            'categories' => implode($cats,','),
            'title' => $notice->getTitle(),
            'firstname' => $notice->getFirstname(),
            'lastname' => $notice->getLastname(),
            'published' => $notice->getPublished()->format('Y-m-d'),
            'unpublished' => $notice->getUnpublished()->format('Y-m-d'),
            'body' => $notice->getBody()
        ));
        return $this;
    }
}
