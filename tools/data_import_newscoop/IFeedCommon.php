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
 * NewsML reader Interface
 */
interface IFeedCommon
{
    /**
     * Create reader from SimpleXMLElement.
     *
     * @param SimpleXMLElement $root
     */
    public function __construct(\SimpleXMLElement $root);

    /**
     * Can this reader understand the XML file?
     *
     * @param SimpleXMLElement $root
     * @return bool
     */
    public static function CanRead(\SimpleXMLElement $root);


    /**
     * Attributes of the given message feed.
     *
     * @return mixed
     */
    public function getAttributes(&$holder, $sort);


}
