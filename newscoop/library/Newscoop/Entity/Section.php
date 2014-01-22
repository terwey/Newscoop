<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl.txt
 */

namespace Newscoop\Entity;

use Doctrine\ORM\Mapping AS ORM;

/**
 * Section entity
 * @ORM\Entity(repositoryClass="Newscoop\Entity\Repository\SectionRepository")
 * @ORM\Table(name="Sections")
 */
class Section
{
    /**
     * Provides the class name as a constant.
     */
    const NAME = __CLASS__;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer")
     * @var int
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\Entity\Publication")
     * @ORM\JoinColumn(name="IdPublication", referencedColumnName="Id")
     * @var Newscoop\Entity\Publication
     */
    private $publication;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\Entity\Issue", inversedBy="sections")
     * @ORM\JoinColumn(name="fk_issue_id", referencedColumnName="id")
     * @var Newscoop\Entity\Issue
     */
    private $issue;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\Entity\Language")
     * @ORM\JoinColumn(name="IdLanguage", referencedColumnName="Id")
     * @var Newscoop\Entity\Language
     */
    private $language;

    /**
     * @ORM\Column(type="integer", name="Number")
     * @var int
     */
    private $number;

    /**
     * @ORM\Column(name="Name")
     * @var string
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\Entity\Template")
     * @ORM\JoinColumn(name="SectionTplId", referencedColumnName="Id")
     * @var Newscoop\Entity\Template"
     */
    private $template;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\Entity\Template")
     * @ORM\JoinColumn(name="ArticleTplId", referencedColumnName="Id")
     * @var Newscoop\Entity\Template"
     */
    private $articleTemplate;

    /**
     * Link to topic articles resource
     * @var string
     */
    private $articlesLink;

    /**
     * Getter for id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Setter for id
     *
     * @param int $id Value to set
     *
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set link to topic articles resource
     * @param string $articlesLink Link to topic articles resource
     */
    public function setArticlesLink($articlesLink)
    {
        $this->articlesLink = $articlesLink;

        return $this;
    }

    /**
     * Get link to topic articles resource
     * @return string Link to topic articles resource
     */
    public function getArticlesLink()
    {
        return $this->articlesLink;
    }

    /**
     * @param int $number
     * @param string $name
     * @param Newscoop\Entity\Issue $issue
     */
    public function __construct($number, $name, $issue = null)
    {
        $this->number = (int) $number;
        $this->name = (string) $name;

        if ($issue !== null) {
            $this->issue = $issue;
            $this->issue->addSection($this);
            $this->publication = $this->issue->getPublication();
            $this->language = $this->issue->getLanguage();
        }
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
        return $this->language->getId();
    }

    /**
     * Get language name
     *
     * @return string
     */
    public function getLanguageName()
    {
        return $this->language->getName();
    }

    /**
     * Get number
     *
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set template
     *
     * @param Newscoop\Entity\Template $template
     * @return Newscoop\Entity\Section
     */
    public function setTemplate(Template $template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * Set article template
     *
     * @param Newscoop\Entity\Template $template
     * @return Newscoop\Entity\Section
     */
    public function setArticleTemplate(Template $template)
    {
        $this->articleTemplate = $template;
        return $this;
    }

    /**
     * Get the issue assigned to this section
     *
     * @return Newscoop\Entity\Issue
     */
    public function getIssue()
    {
        return $this->issue;
    }

    /**
     * Get name of section with language
     *
     * @return string
     */
    public function getNameAndLanguage()
    {
        return $this->getName() .' ('.$this->getLanguageName().')';
    }

    /**
     * String representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getNameAndLanguage();
    }
}
