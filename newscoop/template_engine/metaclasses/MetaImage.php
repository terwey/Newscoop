<?php
/**
 * @package Campsite
 */

/**
 * Includes
 */
require_once($GLOBALS['g_campsiteDir'].'/classes/Image.php');
require_once($GLOBALS['g_campsiteDir'].'/template_engine/metaclasses/MetaDbObject.php');

/**
 * @package Campsite
 */
final class MetaImage extends MetaDbObject {

    public function __construct($p_imageId = null)
    {
        $this->m_dbObject = new Image($p_imageId);
        if (!$this->m_dbObject->exists()) {
            $this->m_dbObject = new Image();
        }

        $this->m_properties['number'] = 'Id';
        $this->m_properties['photographer'] = 'Photographer';
        $this->m_properties['place'] = 'Place';
        $this->m_properties['description'] = 'Description';
        $this->m_properties['date'] = 'Date';
        $this->m_properties['last_update'] = 'LastModified';
        $this->m_properties['caption'] = 'Description';

        $this->m_customProperties['year'] = 'getYear';
        $this->m_customProperties['mon'] = 'getMonth';
        $this->m_customProperties['wday'] = 'getWeekDay';
        $this->m_customProperties['mday'] = 'getMonthDay';
        $this->m_customProperties['yday'] = 'getYearDay';
        $this->m_customProperties['hour'] = 'getHour';
        $this->m_customProperties['min'] = 'getMinute';
        $this->m_customProperties['sec'] = 'getSecond';
        $this->m_customProperties['mon_name'] = 'getMonthName';
        $this->m_customProperties['wday_name'] = 'getWeekDayName';
        $this->m_customProperties['article_index'] = 'getArticleIndex';
        $this->m_customProperties['defined'] = 'defined';
        $this->m_customProperties['imageurl'] = 'getImageUrl';
        $this->m_customProperties['thumbnailurl'] = 'getThumbnailUrl';
        $this->m_customProperties['filerpath'] = 'getImageRelativePath';
        $this->m_customProperties['is_local'] = 'isLocal';
        $this->m_customProperties['type'] = 'getType';
    } // fn __construct


    protected function getYear()
    {
        $timestamp = strtotime($this->m_dbObject->getProperty('Date'));
        $date_time = getdate($timestamp);
        return $date_time['year'];
    }


    protected function getMonth()
    {
        $timestamp = strtotime($this->m_dbObject->getProperty('Date'));
        $date_time = getdate($timestamp);
        return $date_time['mon'];
    }


    protected function getWeekDay()
    {
        $timestamp = strtotime($this->m_dbObject->getProperty('Date'));
        $date_time = getdate($timestamp);
        return $date_time['wday'];
    }


    protected function getMonthDay()
    {
        $timestamp = strtotime($this->m_dbObject->getProperty('Date'));
        $date_time = getdate($timestamp);
        return $date_time['mday'];
    }


    protected function getYearDay()
    {
        $timestamp = strtotime($this->m_dbObject->getProperty('Date'));
        $date_time = getdate($timestamp);
        return $date_time['yday'];
    }


    protected function getHour()
    {
        $timestamp = strtotime($this->m_dbObject->getProperty('Date'));
        $date_time = getdate($timestamp);
        return $date_time['hours'];
    }


    protected function getMinute()
    {
        $timestamp = strtotime($this->m_dbObject->getProperty('Date'));
        $date_time = getdate($timestamp);
        return $date_time['minutes'];
    }


    protected function getSecond()
    {
        $timestamp = strtotime($this->m_dbObject->getProperty('Date'));
        $date_time = getdate($timestamp);
        return $date_time['seconds'];
    }


    public function getImageUrl()
    {
        $url = $this->m_dbObject->getImageUrl();
        return $url;
    }


    public function getThumbnailUrl()
    {
        $url = $this->m_dbObject->getThumbnailUrl();
        return $url;
    }

    protected function getImageRelativePath()
    {
        global $Campsite;

        $imagesdir = basename(rtrim($Campsite['IMAGE_DIRECTORY'], '/'));
        return (string) $imagesdir . '/' . $this->m_dbObject->getImageFileName();
    }

    protected function getMonthName() {
        $dateTime = new MetaDatetime($this->m_dbObject->getProperty('Date'));
        return $dateTime->getMonthName();
    }


    protected function getWeekDayName() {
        $dateTime = new MetaDatetime($this->m_dbObject->getProperty('Date'));
        return $dateTime->getWeekDayName();
    }


    protected function isLocal()
    {
    	return $this->m_dbObject->isLocal();
    }

    protected function getType()
    {
    	return $this->m_dbObject->getType();
    }


    /**
     * Returns the index of the current image inside the article.
     * If the image doesn't belong to the article returns null.
     *
     * @return int
     */
    protected function getArticleIndex() {
        return CampTemplate::singleton()->context()->article->image_index;
    }

} // class MetaSection

?>
