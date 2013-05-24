<?php
/**
 * @package Newscoop
 * @copyright 2012 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 */
class Admin_Form_Slideshow extends Zend_Form
{
    /**
     */
    public function init()
    {
        $this->addElement('text', 'headline', array(
            'label' => $this->translator->trans('Headline'),
            'required' => true,
        ));

        $this->addElement('text', 'slug', array(
            'label' => $this->translator->trans('Slug'),
        ));

        $this->addElement('text', 'description', array(
            'label' => $this->translator->trans('Description'),
        ));

        $this->addElement('submit', 'submit', array(
            'label' => $this->translator->trans('Save'),
            'ignore' => true,
        ));
    }

    /**
     * Set defaults by given entity
     *
     * @param Newscoop\Package\Package $package
     * @return Admin_Form_Slideshow
     */
    public function setDefaultsFromEntity(\Newscoop\Package\Package $package)
    {
        $this->setDefaults(array(
            'headline' => $package->getHeadline(),
            'slug' => $package->getSlug(),
        ));

        return $this;
    }
}
