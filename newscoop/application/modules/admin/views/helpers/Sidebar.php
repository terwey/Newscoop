<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * Sidebar helper
 */
class Admin_View_Helper_Sidebar extends Zend_View_Helper_Abstract
{
    const SIDEBAR = 'sidebar';

    /**
     * Add content to sidebar
     *
     * @param string $title
     * @param string $content
     * return @void
     */
    public function sidebar($title, $content)
    {
        $this->view->placeholder(self::SIDEBAR)->captureStart();
        echo '<h3 class="label">', $title, '</h3>', $content;
        $this->view->placeholder(self::SIDEBAR)->captureEnd();
    }
}
