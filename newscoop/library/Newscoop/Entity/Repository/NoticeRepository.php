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
        //$qb->from('Newscoop\Entity\Comment\Commenter', 'c')
            //->from('Newscoop\Entity\Article', 'a');
        $andx = $qb->expr()->andx();
        //$andx->add($qb->expr()->eq('e.language', new Expr\Literal('a.language')));
        //$andx->add($qb->expr()->eq('e.thread', new Expr\Literal('a.number')));
        //$andx->add($qb->expr()->eq('e.commenter', new Expr\Literal('c.id')));

        if (!empty($p_params['sSearch'])) {
            $this->buildWhere($p_cols, $p_params['sSearch'], $qb, $andx);
        }

        if (!empty($p_params['sFilter'])) {
            $this->buildFilter($p_cols, $p_params['sFilter'], $qb, $andx);
        }

        // sort
        /*
        if (isset($p_params["iSortCol_0"])) {
            $cols = array_keys($p_cols);
            $sortId = $p_params["iSortCol_0"];
            $sortBy = $cols[$sortId];
            $dir = $p_params["sSortDir_0"] ? : 'asc';
            switch ($sortBy) {
                case 'commenter':
                    $qb->orderBy("c.name", $dir);
                    break;
                case 'thread':
                    $qb->orderBy("a.name", $dir);
                    break;
                case 'threadorder':
                    $qb->orderBy("e.thread_order", $dir);
                    break;
                case 'comment':
                case 'index':
                    $qb->orderBy("e.time_created", $dir);
                    break;
                default:
                    $qb->orderBy("e." . $sortBy, $dir);
            }
        }*/
        //$qb->where($andx);
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
    public function getCount(array $p_params = null, array $p_cols = array())
    {
        $qb = $this->createQueryBuilder('e');
        //$qb->from('Newscoop\Entity\Comment\Commenter', 'c')
          //  ->from('Newscoop\Entity\Article', 'a');
       /* $andx = $qb->expr()->andx();
        $andx->add($qb->expr()->eq('e.language', new Expr\Literal('a.language')));
        $andx->add($qb->expr()->eq('e.thread', new Expr\Literal('a.number')));
        $andx->add($qb->expr()->eq('e.commenter', new Expr\Literal('c.id')));

        if (is_array($p_params) && !empty($p_params['sSearch'])) {
            $this->buildWhere($p_cols, $p_params['sSearch'], $qb, $andx);
        }

        if (is_array($p_params) && !empty($p_params['sFilter'])) {
            $this->buildFilter($p_cols, $p_params['sFilter'], $qb, $andx);
        }

        $qb->where($andx);*/
        $qb->select('COUNT(e)');
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Build where condition
     *
     * @param array $cols
     * @param string $search
     * @return Doctrine\ORM\Query\Expr
     */
    protected function buildWhere(array $p_cols, $p_search, $qb, $andx)
    {
        $orx = $qb->expr()->orx();
        $orx->add($qb->expr()->like("c.name", $qb->expr()->literal("%{$p_search}%")));
        $orx->add($qb->expr()->like("a.name", $qb->expr()->literal("%{$p_search}%")));
        $orx->add($qb->expr()->like("e.subject", $qb->expr()->literal("%{$p_search}%")));
        $orx->add($qb->expr()->like("e.message", $qb->expr()->literal("%{$p_search}%")));
        return $andx->add($orx);
    }

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
                    $mapper = array_flip(Comment::$status_enum);
                    foreach ($values as $value) {
                        $orx->add($qb->expr()->eq('e.status', $mapper[$value]));
                    }
                    break;
                case 'id':
                case 'forum':
                case 'thread':
                case 'language':
                    foreach ($values as $value) {
                        $orx->add($qb->expr()->eq("e.$key", $value));
                    }
                    break;
                case 'recommended':
                    foreach ($values as $value) {
                        $orx->add($qb->expr()->eq('e.recommended', $value));
                    }
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
    public function getNotices($hydration = 1)
    {
        $qb = $this->createQueryBuilder('n');

        $qb->select('n');
            //->leftJoin('n.tags', 't');

       /* if (isset($tags) && count($tags)) {
                //->where('t.resource_id = n.id');
            foreach ($tags as $tag) {
                $qb->andWhere('t.name = :tag_name')
                    ->setParameter('tag_name', $tag);
            }
        }*/

        $now = new \DateTime('now');
        return $qb
            ->andWhere('n.published <= :published')
            ->setParameter('published', $now)
            ->getQuery()
            ->getResult($hydration);
    }


}
