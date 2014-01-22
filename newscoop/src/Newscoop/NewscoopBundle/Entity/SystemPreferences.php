<?php
/**
 * @package Newscoop\NewscoopBundle
 * @author Rafał Muszyński <rafal.muszynski@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\NewscoopBundle\Entity;

use Doctrine\ORM\Mapping AS ORM;

/**
 * System Preferences entity
 *
 * @ORM\Entity()
 * @ORM\Table(name="SystemPreferences")
 */
class SystemPreferences 
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name="id")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100, name="varname")
     * @var string
     */
    public $option;

    /**
     * @ORM\Column(type="string", length=100, name="value", nullable=true)
     * @var string
     */
    private $value;

    /**
     * @ORM\Column(type="datetime", name="last_modified")
     * @var datetime
     */
    private $created_at;

    public function __construct() {
        $this->setCreatedAt(new \DateTime());
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set value
     *
     * @param  string $value
     * @return string
     */
    public function setValue($value)
    {
        $this->value = $value;
        
        return $this;
    }

    /**
     * Set option
     *
     * @param  string $option
     * @return string
     */
    public function setOption($option)
    {
        $this->option = $option;
        
        return $this;
    }

    /**
     * Set create date
     *
     * @param datetime $created_at
     * @return datetime
     */
    public function setCreatedAt(\DateTime $created_at)
    {
        $this->created_at = $created_at;
        
        return $this;
    }
}