<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

class Admin_Form_Subscription_SectionAddForm extends Zend_Form
{
    public function init()
    {
        $this->addElement('select', 'language', array(
            'label' => $this->translator->trans('Language'),
            'multioptions' => array(
                'select' => $this->translator->trans('Individual languages'),
                'all' => $this->translator->trans('Regardless of the language'),
            ),
        ));

        $this->addElement('multiselect', 'sections_select', array(
            'label' => $this->translator->trans('Sections'),
            // multioptions from controller
        ));

        $this->addElement('multiselect', 'sections_all', array(
            'label' => $this->translator->trans('Sections'),
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

        $this->addElement('submit', 'submit', array(
            'label' => $this->translator->trans('Save'),
        ));
    }
}
