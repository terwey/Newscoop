<?php
/**
 * @package Newscoop
 * @copyright 2012 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 */
class Admin_Form_NoticeCategory extends Zend_Form
{
    /**
     */
    public function init()
    {
        $this->addElement('hidden', 'id');

        $parent = new Zend_Form_Element_Select('parent');
        $parent->setLabel('As child of:');
        $parent->addMultiOption('', 'Create new Category');
        $this->addElement($parent);

        $this->addElement('text', 'title', array(
            'label' => getGS('TITLE'),
        ));

        $this->addElement('submit', 'submit', array(
            'label' => getGS('save'),
        ));

    }

    /**
     * Set default for given entity
     *
     * @param Newscoop\Package\Item $item
     */
    public function setDefaultsFromEntity(\Newscoop\Entity\NoticeCategory $notice)
    {

    }

    public function setCategories(array $categories)
    {
        $this->getElement('parent')->addMultiOptions($categories);
        return $this;
    }
}
