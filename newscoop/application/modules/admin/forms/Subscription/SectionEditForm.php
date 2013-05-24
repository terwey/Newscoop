<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

class Admin_Form_Subscription_SectionEditForm extends Zend_Form
{
    public function init()
    {
        $this->addElement('text', 'name', array(
            'label' => $this->translator->trans('Section'),
            'readonly' => true,
        ));

        $this->addElement('text', 'language', array(
            'label' => $this->translator->trans('Language'),
            'readonly' => true,
        ));

        $this->addElement('text', 'start_date', array(
            'label' => $this->translator->trans('Start'),
            'required' => true,
            'class' => 'date',
        ));

        $this->addElement('text', 'days', array(
            'label' => $this->translator->trans('Days'),
            'required' => true,
        ));

        $this->addElement('text', 'paid_days', array(
            'label' => $this->translator->trans('Paid Days'),
            'required' => true,
        ));

        $this->addElement('submit', 'submit', array(
            'label' => $this->translator->trans('Save'),
        ));
    }
}
