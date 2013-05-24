<?php
/**
 * @package Newscoop
 * @copyright 2012 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 */
class Admin_Form_SlideshowVideoItem extends Zend_Form
{
    /**
     */
    public function init()
    {
        $this->addElement('text', 'url', array(
            'label' => $this->translator->trans('URL'),
            'required' => true,
        ));

        $this->addElement('submit', 'submit', array(
            'label' => $this->translator->trans('Add video'),
            'ignore' => true,
        ));
    }
}
