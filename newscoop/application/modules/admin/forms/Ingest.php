<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 */
class Admin_Form_Ingest extends Zend_Form
{
    /**
     */
    public function init()
    {
        $this->addElement('hash', 'csrf');

        $this->addElement('select', 'type', array(
            'label' => $this->translator->trans('Type'),
            'required' => true,
            'multioptions' => array(
                'reuters' => 'Thomson Reuters',
            ),
        ));

        $config = new Zend_Form_SubForm();

        $config->addElement('text', 'username', array(
            'label' => $this->translator->trans('Username'),
            'required' => true,
            'filters' => array(
                'stringTrim',
            ),
        ));

        $config->addElement('text', 'password', array(
            'label' => $this->translator->trans('Password'),
            'required' => true,
            'filters' => array(
                'stringTrim',
            ),
        ));

        $this->addSubForm($config, 'config');

        $this->addElement('submit', 'submit', array(
            'label' => $this->translator->trans('Add'),
            'ignore' => true,
        ));
    }
}
