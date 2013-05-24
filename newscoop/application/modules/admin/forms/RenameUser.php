<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 */
class Admin_Form_RenameUser extends Zend_Form
{
    /**
     */
    public function init()
    {
        $this->addElement('hash', 'csrf');

        $this->addElement('text', 'username', array(
            'label' => $this->translator->trans('Username'),
            'required' => true,
            'filters' => array(
                'stringTrim',
            ),
            'validators' => array(
                array('stringLength', false, array(5, 80)),
            ),
        ));

        $this->addElement('submit', 'submit', array(
            'id' => 'save_button',
            'label' => $this->translator->trans('Save'),
            'ignore' => true,
        ));
    }
}
