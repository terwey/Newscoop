<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl.txt
 */

namespace Newscoop\Entity;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Notices entity
 * @Entity(repositoryClass="Newscoop\Entity\Repository\NoticeRepository")
 * @Table(name="Notice")
 */
class Notice implements \DoctrineExtensions\Taggable\Taggable
{
    /**
     * Provides the class name as a constant.
     */
    const NAME = __CLASS__;

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue
     */
    private $id;

    /**
     * ManyToOne(targetEntity="Newscoop\Entity\Publication")
     * JoinColumn(name="IdPublication", referencedColumnName="Id")
     * @var Newscoop\Entity\Publication
     */
    private $publication;

    /**
     * ManyToOne(targetEntity="Newscoop\Entity\Language")
     * JoinColumn(name="IdLanguage", referencedColumnName="Id")
     * @var Newscoop\Entity\Language
     */
    private $language;

    /**
     * @Column(name="title")
     * @var string
     */
    private $title = '';

    /**
     * @Column(name="sub_title")
     * @var string
     */
    private $sub_title = '';

    /**
     * @Column(name="body")
     * @var string
     */
    private $body = '';

    /**
     * @var datetime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @Column(type="datetime")
     */
    private $created;

    /**
     * @var datetime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @Column(type="datetime")
     */
    private $updated;

    /**
     * @var datetime $published
     *
     * @Column(type="datetime")
     */
    private $published;

    /**
     * @var string $status
     *
     * @Column(type="string")
     */
    private $status;

    /**
     * @var string $firstname
     *
     * @Column(type="string")
     */
    private $firstname;

    /**
     * @var string $lastname
     *
     * @Column(type="string")
     */
    private $lastname;

    /**
     * @ManyToMany(targetEntity="DoctrineExtensions\Taggable\Entity\Tag")
     * @JoinTable(name="Tagging",
     *      joinColumns={@JoinColumn(name="resource_id", referencedColumnName="id")}
     *
     *      )
     */
    private $tags;

    private $tagType = 'notice';

    /**
     * @param int $number
     * @param Newscoop\Entity\Publication $publication
     */
    public function __construct()
    {/*
        if ($publication !== null) {
            $this->publication = $publication;
            $this->language = $language !== null ? $language : $this->publication->getDefaultLanguage();
        }*/
    }

    /**
     * Get language
     *
     * @return Newscoop\Entity\Language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Get language id
     *
     * @return int
     */
    public function getLanguageId()
    {
        return $this->language !== null ? $this->language->getId() : null;
    }

    /**
     * Get publication
     *
     * @return Publication
     */
    public function getPublication()
    {
        return $this->publication;
    }

    /**
     * Get publication Id
     *
     * @return int
     */
    public function getPublicationId()
    {
        return $this->publication !== null ? $this->publication->getId() : null;
    }

    /**
     * Get name of the issue
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set title of notice
     *
     * @return string
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get short name of the issue
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * sets the unique taggable resource type
     *
     * @return self
     */
    function setTaggableType($tagType = 'notice')
    {
        $this->tagType = $tagType;
        return $this;
    }
    /**
     * Returns the unique taggable resource type
     *
     * @return string
     */
    function getTaggableType()
    {
        return  $this->tagType;
    }

    /**
     * Returns the unique taggable resource identifier
     *
     * @return string
     */
    function getTaggableId()
    {
        return $this->getId();
    }

    /**
     * Returns the collection of tags for this Taggable entity
     *
     * @return Doctrine\Common\Collections\Collection
     */
    function getTags()
    {
        $this->tags = $this->tags ?: new ArrayCollection();
        return $this->tags;
    }

    public function getId()
    {
        return $this->id;
    }


    /**
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * @param \Newscoop\Entity\datetime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return \Newscoop\Entity\datetime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param string $firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    /**
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @param string $lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    /**
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @param \Newscoop\Entity\datetime $published
     */
    public function setPublished($published)
    {
        $this->published = $published;
    }

    /**
     * @return \Newscoop\Entity\datetime
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $sub_title
     */
    public function setSubTitle($sub_title)
    {
        $this->sub_title = $sub_title;
    }

    /**
     * @return string
     */
    public function getSubTitle()
    {
        return $this->sub_title;
    }

    public function setTagType($tagType)
    {
        $this->tagType = $tagType;
    }

    public function getTagType()
    {
        return $this->tagType;
    }

    /**
     * @param \Newscoop\Entity\datetime $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * @return \Newscoop\Entity\datetime
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}

