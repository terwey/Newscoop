<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\Entity\Repository;

use Newscoop\Entity\Notice;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Newscoop\Datatable\Source as DatatableSource;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * Notice repository.
 */
class NoticeCategoryRepository extends NestedTreeRepository
{
    /**
     * Returns a query builder built to return tag counts for a given type
     *
     * @see getTagsWithCountArray
     * @param $taggableType
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getCatsWithCountArrayQueryBuilder()
    {
        $qb = $this->getCatsQueryBuilder()
            ->groupBy('tagging.tag')
            ->select('tag.'.$this->tagQueryField.', COUNT(tagging.tag) as tag_count')
            ->orderBy('tag_count', 'DESC')
        ;

        return $qb;
    }

    /**
     * Returns a query builder returning tags for a given type
     *
     * @param string $taggableType
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getCatsQueryBuilder()
    {
        return $this->createQueryBuilder('nc')
            ->join('nc.tagging', 'categorization')
            ->where('tagging.resourceType = :resourceType')
            ->setParameter('resourceType', $taggableType)
            ;
    }

    /**
     * For a specific taggable type, this returns an array where they key
     * is the tag and the value is the number of times that tag is used
     *
     * @param string $taggableType The taggable type / resource type
     * @param null|integer $limit The max results to return
     * @return array
     */
    public function getTagsWithCountArray($taggableType, $limit = null)
    {
        $qb = $this->getTagsWithCountArrayQueryBuilder($taggableType);

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        $tags = $qb->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR)
        ;

        $arr = array();
        foreach ($tags as $tag) {
            $count = $tag['tag_count'];

            // don't include orphaned tags
            if ($count > 0) {
                $tagName = $tag[$this->tagQueryField];
                $arr[$tagName] = $count;
            }
        }

        return $arr;
    }

}
