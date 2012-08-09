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


/**
 * Notice repository.
 */
class NoticeRepository extends DatatableSource
{
    /**
     * Get new instance of the comment
     *
     * @return \Newscoop\Entity\Comment
     */
    public function getPrototype()
    {
        return new Notice;
    }

    /**
     * Get data for table
     *
     * @param array $p_params
     * @param array $cols
     * @return Comment[]
     */
    public function getData(array $p_params, array $p_cols)
    {
        $qb = $this->createQueryBuilder('e');

        if (!empty($p_params['sSearch'])) {
            //$this->buildWhere($p_cols, $p_params['sSearch'], $qb, $andx);
        }

        if (!empty($p_params['sFilter'])) {
            $andx = $qb->expr()->andx();

            $andx = $this->buildFilter($p_cols, $p_params['sFilter'], $qb,$andx);
            $qb->where($andx);
        }

        // sort
        if (isset($p_params["iSortCol_0"])) {
            $cols = array_keys($p_cols);
            $sortId = $p_params["iSortCol_0"];
            $sortBy = $cols[$sortId];
            $dir = $p_params["sSortDir_0"] ? : 'asc';
            switch ($sortBy) {
                case 'lastname':
                    $qb->orderBy("e.lastname", $dir);
                    break;
                case 'published':
                    $qb->orderBy("e.published", $dir);
                    break;
                case 'id':
                    $qb->orderBy("e.id", $dir);
                    break;
                default:
                    $qb->orderBy("e." . $sortBy, $dir);
            }
        }


        // limit
        if (isset($p_params['iDisplayLength'])) {
            $qb->setFirstResult((int)$p_params['iDisplayStart'])->setMaxResults((int)$p_params['iDisplayLength']);
        }

        return $qb->getQuery()->getResult();
    }



    /**
     * Get entity count
     *
     * @param array $p_params|null
     * @param array $p_cols|null
     *
     * @return int
     */

/*    public function getCount(array $p_params = null, array $p_cols = array())
    {
        $qb = $this->createQueryBuilder('e');
        //$qb->from('Newscoop\Entity\Comment\Commenter', 'c')
          //  ->from('Newscoop\Entity\Article', 'a');
        $andx = $qb->expr()->andx();
        $andx->add($qb->expr()->eq('e.language', new Expr\Literal('a.language')));

        if (is_array($p_params) && !empty($p_params['sSearch'])) {
            //$this->buildWhere($p_cols, $p_params['sSearch'], $qb, $andx);
        }
        if (is_array($p_params) && !empty($p_params['sFilter'])) {
            $this->buildFilter($p_cols, $p_params['sFilter'], $qb, $andx);
        }
        $qb->where($andx);
        $qb->select('COUNT(e)');
        return $qb->getQuery()->getSingleScalarResult();
    }*/

    /**
     * Build where condition
     *
     * @param array $cols
     * @param string $search
     * @return Doctrine\ORM\Query\Expr
     */
    /*protected function buildWhere(array $p_cols, $p_search, $qb, $andx)
    {
        $orx = $qb->expr()->orx();
        $orx->add($qb->expr()->like("e.title", $qb->expr()->literal("%{$p_search}%")));
        $orx->add($qb->expr()->like("e.body", $qb->expr()->literal("%{$p_search}%")));
        return $andx->add($orx);
    }
*/
    /**
     * Build filter condition
     *
     * @param array $p_
     * @param string $p_cols
     * @param
     * @return Doctrine\ORM\Query\Expr
     */
    protected function buildFilter(array $p_cols, array $p_filter, $qb, $andx)
    {
        foreach ($p_filter as $key => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }
            $orx = $qb->expr()->orx();
            switch ($key) {
                case 'status':
                    $mapper = array_flip(Notice::$status_enum);
                    foreach ($values as $value) {
                        if(isset($mapper[$value])){
                            $orx->add($qb->expr()->eq('e.status', $mapper[$value]));
                        }
                    }
                    break;
            }
            $andx->add($orx);
        }
        return $andx;
    }

    /**
     * Get notices
     *
     * @return array
     */
    public function getNotices($hydration = 1, $queryParts)
    {
        $qb = $this->createQueryBuilder('n');

        if (isset($queryParts) && count($queryParts)) {
            $ids = array();
            foreach ($queryParts as $catId) {
                $ids[] = $catId;
            }
            //$qb->expr()->gt($qb->expr()->count('cat_counter'), count($ids));
            //$qb->add('where', $qb->expr()->in('cat.id', $ids));
        }

        $qb->select('n,cat')
            //->select('n, cat2, COUNT(cat2.id) AS cat_counter')
            ->leftjoin('n.categories', 'cat')
            ->leftjoin('n.categories', 'cat2')

        ;

        if(count($ids)){
            $qb->andwhere($qb->expr()->in('cat.id',$ids));
            $qb->addGroupBy('cat2.id')
                ->having($qb->expr()->gte($qb->expr()->count('cat.id'), count($ids)));
        }


        $now = new \DateTime('now');
        $test = $qb
            ->andWhere('n.published <= :published')
            ->setParameter('published', $now)
            ->andWhere('n.status <= :status')
            ->setParameter('status', 0)
            ->orderBy('n.id','DESC')
            ->getQuery();
        //var_dump($test->getSql());
        //exit;
            return $test->getResult($hydration);
    }


    /**
     * Returns a query builder built to return tag counts for a given type
     *
     * @see getTagsWithCountArray
     * @param $taggableType
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getTagsWithCountArrayQueryBuilder($taggableType)
    {
        $qb = $this->getTagsQueryBuilder($taggableType)
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
    public function getTagsQueryBuilder($taggableType)
    {
        return $this->createQueryBuilder('tag')
            ->join('tag.tagging', 'tagging')
            ->where('tagging.resourceType = :resourceType')
            ->setParameter('resourceType', $taggableType)
            ;
    }

}
