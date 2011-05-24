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
     * Get number of items.
     *
     * @return int
     */
    public function getHeadLine();

    /**
     * Get headline node.
     *
     * @return array or null
     */
    public function getSlugLine();

    /**
     * Texts of the news.
     *
     * @return mixed
     */
    public function getContentTexts();

    /**
     * Origianl linked of the news.
     *
     * @return string
     */
    public function getLink();

    /**
     * Creator info of the news.
     *
     * @return string
     */
    public function getCreator();

    /**
     * Service info of the news.
     *
     * @return string
     */
    public function getService();

    /**
     * Rights info of the news.
     *
     * @return string
     */
    public function getCopyright();

    /**
     * GUID of the news.
     *
     * @return string
     */
    public function getNewsID();

    /**
     * Title of the news.
     *
     * @return string
     */
    public function getTitle();

    /**
     * Attributes of the given message feed.
     *
     * @return mixed
     */
    public function getDependencies();

    /**
     * Get images from the news content.
     *
     * @return mixed
     */
    public function getContentImages();

    /**
     * Item of a real news, or a linked media.
     *
     * @return mixed
     */
    public function isNews();


}
