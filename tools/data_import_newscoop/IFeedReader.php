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
     * Get single node.
     *
     * @return array or null
     */
    public function item($index);

    /**
     * Get number of items, incl. linked media.
     *
     * @return int
     */
    public function count();

    /**
     * Get number of real news items.
     *
     * @return int
     */
    public function countNews();

}
