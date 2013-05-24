<?php

class Admin_Form_Theme extends Zend_Form
{
    public function init()
    {
        $this->addElement('text', 'name', array(
            'label' => $this->translator->trans('Theme name'),
            'required' => True,
        ));

        $this->addElement('text', 'required-version', array(
            'label' => $this->translator->trans('Required Newscoop version'),
            'description' => $this->translator->trans( 'or higher' ),
            'class' => 'small',
            'readonly' => True,
        ));

        $this->addElement('text', 'theme-version', array(
            'label' => $this->translator->trans( 'Theme version' ),
            'class' => 'small',
            'readonly' => True,
        ));

        $this->setAttrib('autocomplete', 'off');
        $this->setAction('')->setMethod(Zend_Form::METHOD_POST);
    }
}
