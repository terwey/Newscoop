<?php
/**
 * @package Newscoop
 *
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl.txt
 * @link http://www.sourcefabric.org
 */

namespace NewsML;

/*
 * NewsML one news item Interface
 */
interface IFeedNews
{
    /**
     * Create reader from SimpleXMLElement.
     *
     * @param SimpleXMLElement $root
     */
    // public function __construct(\SimpleXMLElement $root);

    /**
     * Get single node.
     *
     * @return array or null
     */
    public function getSlugLine();

    /**
     * Get number of items.
     *
     * @return int
     */
    public function getHeadLine();

    public function getContentText();
    public function getLink();
    public function getCreator();
    public function getService();
    public function getCopyright();
    public function getNewsID();
    //public function ();

    /**
     * Can this reader understand the XML file?
     *
     * @param SimpleXMLElement $root
     * @return bool
     */
    // public static function CanRead(\SimpleXMLElement $root);


    /**
     * Attributes of the given message feed.
     *
     * @return mixed
     */
    // public function getAttributes(array &$holder, $sort);


}
