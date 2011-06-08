<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * User Group form
 */
class Admin_Form_UserGroup extends Zend_Form
{
    public function init()
    {
        $this->addElement('text', 'name', array(
            'label' => getGS('Name'),
            'required' => true,
        ));

        $this->addElement('submit', 'submit', array(
            'label' => getGS('Save'),
            'ignore' => true,
        ));
    }
}
