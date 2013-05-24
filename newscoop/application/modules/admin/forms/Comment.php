<?php

/**
 * User form
 */
class Admin_Form_Comment extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        /*$user = new Zend_Form_Element_Select('user');
        $user->setLabel($this->translator->trans('Username'))
            ->setRequired(false)
            ->setOrder(30);
        $this->addElement($user);
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


        $this->addElement('text', 'parent', array(
            'label' => $this->translator->trans('Parent'),
            'required' => false,
            'order' => 61,
        ));

        $this->addElement('text', 'subject', array(
            'label' => $this->translator->trans('Subject'),
            'required' => false,
            'order' => 70,
        ));

        $this->addElement('textarea', 'message', array(
            'label' => $this->translator->trans('Subject'),
            'required' => false,
            'order' => 80,
        ));

        $this->addDisplayGroup(array(
            'user'
        ), 'commentsUser', array(
            'legend' => $this->translator->trans('Show comment user'),
            'class' => 'toggle',
            'order' => 35,
        ));

        $this->addDisplayGroup(array(
            'parent'
        ), 'comments_parent', array(
            'legend' => $this->translator->trans('Show parent comment'),
            'class' => 'toggle',
            'order' =>67,
        ));

        $this->addDisplayGroup(array(
            'name',
            'email',
            'url'
        ), 'commentsUser_info', array(
            'legend' => $this->translator->trans('Show comment user details'),
            'class' => 'toggle',
            'order' => 65,
        ));

        $this->addDisplayGroup(array(
            'subject',
            'message'
        ), 'comment_info', array(
            'legend' => $this->translator->trans('Show comment details'),
            'class' => 'toggle',
            'order' => 75,
        ));

        $this->addElement('submit', 'submit', array(
            'label' => $this->translator->trans('Save'),
            'order' => 99,
        ));
    }

    /**
     * Set default values by entity
     *
     * @param Newscoop\Entity\Commenter $commenter
     * @return void
     */
    public function setFromEntity(Comments $comment)
    {
        $this->setDefaults(array(
            'user' => $user->getUser(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'url'   => $user->getUrl()
        ));

    }

}
