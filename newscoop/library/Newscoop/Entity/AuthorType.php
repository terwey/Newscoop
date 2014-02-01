<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl.txt
 */

namespace Newscoop\Entity;

use Doctrine\ORM\Mapping AS ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Newscoop\Utils\Validation;

/**
 * Issue entity
 * @ORM\Entity
 * @ORM\Table(name="AuthorTypes")
 */
class AuthorType
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string", unique=true, name="type")
     */
    private $type;

    /**
     * @ORM\ManyToMany(targetEntity="Newscoop\Entity\Author")
     * @ORM\JoinTable(name="AuthorAssignedTypes",
     *      joinColumns={
     *          @ORM\JoinColumn(name="fk_type_id", referencedColumnName="id")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="fk_author_id", referencedColumnName="id")
     *      }
     *  )
     * @var Doctrine\Common\Collections\ArrayCollection
     */
    private $authors;

    private function __construct()
    {
        $this->authors = new ArrayCollection();
    }

    /**
     * Get type field
     *
     * @return string
     *      The name of the type.
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type field
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getType();
    }
}

