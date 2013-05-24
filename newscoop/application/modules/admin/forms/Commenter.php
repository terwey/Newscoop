<?php
/**
 * Commenter form
 */
class Admin_Form_Commenter extends Zend_Form
{
    public function init()
    {
        /*
        $user = new Zend_Form_Element_Select('user');
        $user->setLabel($this->translator->trans('Username'))
            ->setRequired(true)
            ->setOrder(30);
        */
        $user = new Zend_Form_Element_Text('user');
        $user->setLabel($this->translator->trans('User id'))
            ->setRequired(false)
            ->setOrder(30);
        $this->addElement($user);

        $this->addElement('text', 'name', array(
            'label' => $this->translator->trans('Name'),
            'required' => false,
            'filters' => array(
                'stringTrim',
            ),
            'validators' => array(
                array('stringLength', false, array(1, 128)),
            ),
            'errorMessages' => array($this->translator->trans('Value is not $1 characters long', '1-128')),
            'order' => 40,
        ));

        $this->addElement('text', 'email', array(
            'label' => $this->translator->trans('E-mail'),
            'required' => false,
            'order' => 50,
        ));

        $this->addElement('text', 'url', array(
            'label' => $this->translator->trans('Website'),
            'required' => false,
            'order' => 60,
        ));

        $this->addDisplayGroup(array(
            'name',
            'email',
            'url'
        ), 'commenter_info', array(
            'legend' => $this->translator->trans('Show commenter details'),
            'class' => 'toggle',
            'order' => 70,
        ));

        $this->addDisplayGroup(array(
            'user',
        ), 'commenter', array(
            'legend' => $this->translator->trans('Show commenter'),
            'class' => 'toggle',
            'order' => 20,
        ));

        $this->addElement('submit', 'submit', array(
            'label' => $this->translator->trans('Save'),
            'order' => 99,
        ));
    }

    /**
     * Set default values by entity
     *
     * @param $commenter
     * @return void
     */
    public function setFromEntity($commenter)
    {
        $this->setDefaults(array(
            'user' => $commenter->getUserId(),
            'name' => $commenter->getName(),
            'email' => $commenter->getEmail(),
            'url'   => $commenter->getUrl()
        ));

    }

}
