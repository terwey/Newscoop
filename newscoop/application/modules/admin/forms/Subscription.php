<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * Subscription form
 */
class Admin_Form_Subscription extends Zend_Form
{
    public function init()
    {
        $this->addElement('select', 'publication', array(
            'label' => $this->translator->trans('Publication'),
            'required' => true,
        ));

        $this->addElement('select', 'language_set', array(
            'label' => $this->translator->trans('Language'),
            'multioptions' => array(
                'select' => $this->translator->trans('Individual languages'),
                'all' => $this->translator->trans('Regardless of the language'),
            ),
        ));

        $this->addElement('multiselect', 'languages', array(
            'required' => isset($_POST['language_set']) && $_POST['language_set'] == 'select', // check only if language_set == select
            'validators' => array(
                array(new Zend_Validate_Callback(function($value, $context) {
                    return $context['language_set'] == 'all' || !empty($value);
                }), true),
            ),
        ));

        $this->getElement('languages')->setAutoInsertNotEmptyValidator(false);

        $this->addElement('select', 'sections', array(
            'label' => $this->translator->trans('Sections'),
            'multioptions' => array(
                'Y' => $this->translator->trans('Add sections now'),
                'N' => $this->translator->trans('Add sections later'),
            ),
        ));

        $this->addElement('text', 'start_date', array(
            'label' => $this->translator->trans('Start'),
            'required' => true,
            'class' => 'date',
        ));

        $this->addElement('select', 'type', array(
            'label' => $this->translator->trans('Subscription Type'),
            'multioptions' => array(
                'PN' => $this->translator->trans('Paid (confirm payment now)'),
                'PL' => $this->translator->trans('Paid (payment will be confirmed later)'),
                'T' => $this->translator->trans('Trial'),
            ),
        ));

        $this->addElement('text', 'days', array(
            'label' => $this->translator->trans('Days'),
            'required' => true,
            'validators' => array(
                array('greaterThan', false, array(0)),
            ),
        ));

        $this->addElement('checkbox', 'active', array(
            'label' => $this->translator->trans('Active'),
            'value' => 1,
        ));
    
        $this->addElement('submit', 'submit', array(
            'label' => $this->translator->trans('Add'),
        ));
    }
}

