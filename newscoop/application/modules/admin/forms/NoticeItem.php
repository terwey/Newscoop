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
        $this->setAction('/admin/notice-rest');
        $this->addElement('hidden', 'coords');

        $this->addElement('text', 'title', array(
            'label' => getGS('TITLE'),
        ));

        $this->addElement('text', 'firstname', array(
            'label' => getGS('First name'),
        ));

        $this->addElement('text', 'lastname', array(
            'label' => getGS('Last name'),
        ));

        $this->addElement('text', 'tags', array(
            'label' => getGS('Tags'),
        ));

        $this->addElement('textarea', 'body', array(
            'label' => getGS('CONTENT'),
        ));

        $this->addElement('submit', 'submit', array(
            'label' => getGS('speichern'),
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
        $this->setDefaults(array(
            'id' => $notice->getId(),
            'title' => $notice->getTitle(),
            'firstname' => $notice->getFirstname(),
            'lastname' => $notice->getLastname(),
            'body' => $notice->getBody()
        ));
        return $this;
    }
}
