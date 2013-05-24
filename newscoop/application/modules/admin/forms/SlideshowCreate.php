<?php
/**
 * @package Newscoop
 * @copyright 2012 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 */
class Admin_Form_SlideshowCreate extends Zend_Form
{
    /**
     */
    public function init()
    {
        $this->addElement('text', 'headline', array(
            'label' => $this->translator->trans('Headline'),
            'required' => true,
        ));

        $this->addElement('select', 'rendition', array(
            'label' => $this->translator->trans('Slideshow rendition'),
            'required' => true,
        ));

        $this->addElement('submit', 'submit', array(
            'label' => $this->translator->trans('Create'),
            'ignore' => true,
        ));
    }
}
