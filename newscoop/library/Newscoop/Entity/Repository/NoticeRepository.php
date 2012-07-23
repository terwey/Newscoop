<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\Entity\Repository;

use Doctrine\ORM\EntityRepository,
Newscoop\Entity\Notice;

/**
 * Notice repository.
 */
class NoticeRepository extends EntityRepository
{
    /**
     * Get notices
     *
     * @return array
     */
    public function getNotices($hydration = 1, $tags)
    {
        $qb = $this->createQueryBuilder('n');

        $qb->select('n,t')
            ->leftJoin('n.tags', 't');

        if (isset($tags) && count($tags)) {
                //->where('t.resource_id = n.id');
            foreach ($tags as $tag) {
                $qb->andWhere('t.name = :tag_name')
                    ->setParameter('tag_name', $tag);
            }
        }

        $now = new \DateTime('now');
        return $qb
            ->andWhere('n.published <= :published')
            ->setParameter('published', $now)
            ->getQuery()
            ->getResult($hydration);
    }
}
