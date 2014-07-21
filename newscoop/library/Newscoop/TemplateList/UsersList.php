<?php

/**
 * @author Paweł Mikołajczuk <pawel.mikolajczuk@sourcefabric.org>
 * @package Newscoop
 * @copyright 2013 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl.txt
 */

namespace Newscoop\TemplateList;

/**
 * List users
 */
class UsersList extends PaginatedBaseList
{

    protected function prepareList($criteria, $parameters)
    {
        $em = \Zend_Registry::get('container')->get('em');
        $result = $em->getRepository('Newscoop\Entity\User')->getListByCriteria($criteria, false);
        $list = $this->paginateList($result[0], null, $criteria->maxResults, null, false);
        $list->count = $result[1];

        $tempList = array_map(function ($user) {
              return new \MetaUser($user);
        }, $list->items);

        $list->items = $tempList;

        return $list;
    }

    protected function convertParameters($firstResult, $parameters)
    {
        $this->criteria->orderBy = array();
        // run default simple parameters converting
        parent::convertParameters($firstResult, $parameters);

        // apply attributes as a filters
        if (array_key_exists('attributes', $parameters)) {
            $this->criteria->attributes = $this->parseConstraintsString($parameters['attributes']);
        }

        // convert your special parameters into criteria properties.
        if (array_key_exists('search', $parameters)) {
            $this->criteria->query = $parameters['search'];
        } elseif (array_key_exists('filter', $parameters)) {
            $filter = $parameters['filter'];
            $this->criteria->groups = !empty($parameters['editor_groups']) ? array_map('intval', explode(',', $parameters['editor_groups'])) : array();
            switch ($filter) {
                case 'active':
                    $this->criteria->orderBy = array('points' => 'desc');
                    $this->criteria->excludeGroups = true;
                    break;

                case 'editors':
                    $this->criteria->excludeGroups = false;
                    break;

                default:
                    $this->criteria->groups = array();

                    // example: filter="a-c"
                    if (preg_match('/([a-z])-([a-z])/', $filter, $matches)) {
                        $this->criteria->nameRange = range($matches[1], $matches[2]);
                    } else {
                        \CampTemplate::singleton()->trigger_error("invalid parameter $filter in filter");
                    }
                    break;
            }
        }
    }
}
