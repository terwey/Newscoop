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
interface IFeedReader
{
    /**
     * Create reader from SimpleXMLElement.
     *
     * @param SimpleXMLElement $root
     */
    public function __construct(\SimpleXMLElement $root);

    /**
     * Get single node.
     *
     * @return array or null
     */
    public function item($index);

    /**
     * Get number of items.
     *
     * @return int
     */
    public function count();

    /**
     * Can this reader understand the XML file?
     *
     * @param SimpleXMLElement $root
     * @return bool
     */
    public static function canRead(\SimpleXMLElement $root);
}