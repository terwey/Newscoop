<?php
/**
 * Ban Commenter form
 */
class Admin_Form_Ban extends Zend_Form
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
    public function getDeleteComments()
    {
        return $this->delete_comments;
    }

    /**
     * Getter for the ip checkbox
     *
	 * @return Zend_Form_Element_Checkbox
     */
    public function getElementIp()
    {
        return $this->ip;
    }

    /**
     * Getter for the name checkbox
     *
	 * @return Zend_Form_Element_Checkbox
     */
    public function gettElementName()
    {
        return $this->name;
    }

    /**
     * Getter for the email checkbox
     *
	 * @return Zend_Form_Element_Checkbox
     */
    public function gettElementEmail()
    {
        return $this->email;
    }


    public function init()
    {

        $this->addElement('checkbox', 'name', array(
            'label' => getGS(getGS('Name').":"),
            'required' => false,
            'order' => 10,
        ));

        $this->addElement('checkbox', 'email', array(
            'label' => getGS(getGS('Email').":"),
            'required' => false,
            'order' => 20,
        ));

        $this->addElement('checkbox', 'ip', array(
            'label' => getGS(getGS('Ip').":"),
            'required' => false,
            'order' => 30,
        ));


        $this->addElement('checkbox', 'delete_comments', array(
            'label' => getGS(getGS('Delete all comments?').":"),
            'required' => false,
            'order' => 40,
        ));

        $this->addElement('submit', 'cancel', array(
            'label' => getGS('Cancel'),
            'order' => 98,
        ));

        $this->addElement('submit', 'submit', array(
            'label' => getGS('Save'),
            'order' => 99,
        ));
    }

    /**
     * Set values
     *
     * @param $commenter
     * @param $values
     */
    public function setValues($p_commenter, $p_values)
    {   
        /* @var $name Zend_Form_Element_CheckBox */
        $this->name->setLabel(getGS('Name').":".strip_tags($p_commenter->getName()))
                    ->setChecked($p_values['name']);

        $this->email->setLabel(getGS('Email').":".$p_commenter->getEmail())
                    ->setChecked($p_values['email']);

        $this->ip->setLabel(getGS('Ip').":".$p_commenter->getIp())
                 ->setChecked($p_values['ip']);
    }

}
