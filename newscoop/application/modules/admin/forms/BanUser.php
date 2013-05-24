<?php
/**
 * Ban User form
 */
class Admin_Form_BanUser extends Zend_Form
{

    /**
     * Getter for the submit button
     *
	 * @return Zend_Form_Element_Submit
     */
    public function getSubmit()
    {
        return $this->submit;
    }

    /**
     * Getter for the delete comments
     *
	 * @return Zend_Form_Element_Checkbox
     */
    public function getDeleteMessages()
    {
        return $this->delete_messages;
    }

    /**
     * Getter for the name checkbox
     *
	 * @return Zend_Form_Element_Checkbox
     */
    public function getElementName()
    {
        return $this->name;
    }

    /**
     * Getter for the email checkbox
     *
	 * @return Zend_Form_Element_Checkbox
     */
    public function getElementEmail()
    {
        return $this->email;
    }


    public function init()
    {

        $this->addElement('checkbox', 'name', array(
            'label' => $this->translator->trans($this->translator->trans('Name').":"),
            'required' => false,
            'order' => 10,
        ));

        $this->addElement('checkbox', 'email', array(
            'label' => $this->translator->trans($this->translator->trans('Email').":"),
            'required' => false,
            'order' => 20,
        ));

        $this->addElement('checkbox', 'delete_messages', array(
            'label' => $this->translator->trans('Delete feedback messages?'),
            'required' => false,
            'order' => 40,
        ));

        $this->addElement('submit', 'cancel', array(
            'label' => $this->translator->trans('Cancel'),
            'order' => 98,
        ));

        $this->addElement('submit', 'submit', array(
            'label' => $this->translator->trans('Save'),
            'order' => 99,
        ));
    }

    /**
     * Set values
     *
     * @param $user
     * @param $values
     */
    public function setValues($p_user, $p_values)
    {
        /* @var $name Zend_Form_Element_CheckBox */
        $this->name->setLabel($this->translator->trans('Name').":".$p_user->getName())
                    ->setChecked($p_values['name']);

        $this->email->setLabel($this->translator->trans('Email').":".$p_user->getEmail())
                    ->setChecked($p_values['email']);
    }

}
