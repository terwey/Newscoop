<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Doctrine\ORM\EntityManager;

/**
 * Entity action helper
 */
class Action_Helper_Entity extends Zend_Controller_Action_Helper_Abstract
{
    /** @var Doctrine\ORM\EntityManager */
    private $em;

    /**
     * Init Entity manager
     *
     * @return Action_Helper_Entity
     */
    public function init()
    {
        $this->getManager();
        return $this;
    }

    /**
     * Set entity manager
     *
     * @param Doctrine\ORM\EntityManager
     * @return Action_Helper_Entity
     */
    public function setManager(EntityManager $em)
    {
        $this->em = $em;
        return $this;
    }

    /**
     * Get entity manager
     *
     * @return Doctrine\ORM\EntityManager
     */
    public function getManager()
    {
        if ($this->em === NULL) {
            $doctrine = Zend_Registry::get('doctrine');
            $this->setManager($doctrine->getEntityManager());
        }

        return $this->em;
    }

    /**
     * Get entity repository
     *
     * @params mixed $entity
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository($entity)
    {
        return $this->em->getRepository($this->getClassName($entity));
    }

    /**
     * Flush entity manager
     *
     * @return void
     */
    public function flushManager()
    {
        $this->em->flush();
    }

    /**
     * Get entity by parameter
     *
     * @param mixed $entity
     * @param string $param
     * @return object|NULL
     */
    public function get($entity, $param = 'id')
    {
        $request = $this->getRequest();
        $key = $request->getParam($param);
        if (!$key) {
            throw new InvalidArgumentException("'$param' not set");
        }

        $entity = $this->getClassName($entity);
        $match = $this->em->find($entity, $key);
        if (!$match) {
            throw new InvalidArgumentException("$entity with $param = $key not found");
        }

        return $match;
    }

    /**
     * Direct strategy
     *
     * @param mixed $entity
     * @param string $key
     * @param bool $throw
     * @return object|NULL
     */
    public function direct($entity, $key, $throw = TRUE)
    {
        return $this->get($entity, $key, $throw);
    }

    /**
     * Find entity
     *
     * @param string $entity
     * @param mixed $key
     * @return mixed
     */
    public function find($entity, $key)
    {
        return $this->em->find($entity, $key);
    }

    /**
     * Get entity name
     *
     * @param mixed $entity
     * @return string
     */
    private function getClassName($entity)
    {
        return is_object($entity) ?
            get_class($entity) : (string) $entity;
    }
}
