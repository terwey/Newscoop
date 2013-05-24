<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 */
class Admin_Form_IngestSettings extends Zend_Form
{
    /**
     */
    public function init()
    {
        $this->addElement('hash', 'csrf');

        $this->addElement('text', 'article_type', array(
            'label' => $this->translator->trans('Article Type'),
            'required' => true,
        ));

        $this->addElement('select', 'publication', array(
            'label' => $this->translator->trans('Publication'),
            'multioptions' => array(
                null => $this->translator->trans('None'),
            ),
        ));

        $this->addElement('select', 'section', array(
            'label' => $this->translator->trans('Section'),
            'multioptions' => array(
                null => $this->translator->trans('None'),
            ),
        ));

        $this->addElement('submit', 'submit', array(
            'label' => $this->translator->trans('Save'),
            'ignore' => true,
        ));
    }
}
