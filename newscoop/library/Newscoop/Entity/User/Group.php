<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl.txt
 */

namespace Newscoop\Entity\User;

use Newscoop\Entity\Acl\Role;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;

/**
 * User Group entity
 * @ORM\Entity(repositoryClass="Newscoop\Entity\Repository\User\GroupRepository")
 * @ORM\Table(name="liveuser_groups")
 */
class Group implements \Zend_Acl_Role_Interface
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer", name="group_id")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(name="group_define_name")
     * @var string
     */
    protected $name;

    /**
     * @ORM\OneToOne(targetEntity="Newscoop\Entity\Acl\Role")
     * @var Newscoop\Entity\Acl\Role
     */
    protected $role;
    
    /**
     * @ORM\ManyToMany(targetEntity="Newscoop\Entity\User", mappedBy="groups", indexBy="id")
     */
    protected $users;

    /**
     */
    public function __construct()
    {
        $this->users = new ArrayCollection;
    }
    
    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Newscoop\Entity\User\Group
     */
    public function setName($name)
    {
        $this->name = (string) $name;
        return $this;
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
     * Set role
     *
     * @param Newscoop\Entity\Acl\Role $role
     * @return Newscoop\Entity\User\Group
     */
    public function setRole(Role $role)
    {
        $this->role = $role;
        return $this;
    }

    /**
     * Get role id
     *
     * @return int
     */
    public function getRoleId()
    {
        return $this->role ? $this->role->getId() : 0;
    }

    /**
     * Get role rules
     *
     * @return array
     */
    public function getRoleRules()
    {
        return $this->role ? $this->role->getRules() : array();
    }
    
    /**
     * Get users
     *
     * @return array
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}

