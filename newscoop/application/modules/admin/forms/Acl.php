<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * Acl form
 */
class Admin_Form_Acl extends Zend_Form
{
    public function init()
    {
        $this->addElement('hidden', 'role');
        $this->addElement('hidden', 'next');

        $this->addElement('select', 'resource', array(
            'label' => getGS('Resource'),
            'multioptions' => array('' => getGS('Any resource')),
        ));

        $this->addElement('select', 'action', array(
            'label' => getGS('Action'),
            'multioptions' => array('' => getGS('Any action')),
        ));

        $this->addElement('radio', 'type', array(
            'label' => getGS('Add Rule'),
            'class' => 'acl type',
        ));

        $this->addElement('submit', 'submit', array(
            'label' => getGS('Add'),
        ));
    }
}
