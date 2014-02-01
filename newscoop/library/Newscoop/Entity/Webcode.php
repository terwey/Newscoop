<?php
/**
 * @package Newscoop
 * @copyright 2012 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\Entity;

use Doctrine\ORM\Mapping AS ORM;

/**
 * Webcode entity
 *
 * @ORM\Entity
 * @ORM\Table(name="webcode")
 */
class Webcode
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=80)
     * @var string
     */
    private $webcode;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\Entity\Article", cascade={"persist"})
     * @ORM\JoinColumns({
     *      @ORM\JoinColumn(name="article_number", referencedColumnName="Number"),
     *      @ORM\JoinColumn(name="language_id", referencedColumnName="IdLanguage")
     *  })
     */
    private $article;

    /**
     * @param string $webcode
     * @param Newscoop\Entity\Article $article
     */
    public function __construct($webcode, $article)
    {
        $this->webcode = (string) $webcode;
        $this->article = $article;
    }

    /**
     * Get article
     *
     * @return Newscoop\Entity\Article
     */
    public function getArticle()
    {
        return $this->article;
    }

    /**
     * Get webcode
     *
     * @return string|null
     */
    public function getWebcode()
    {
        return $this->webcode;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->webcode;
    }
}
