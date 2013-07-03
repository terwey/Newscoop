<?php
/**
 * Campsite customized Smarty plugin
 * @package Campsite
 */

/**
 * Builds the Solr FQ query
 *
 * Type:     function
 * Name:     date_filter
 * Purpose:
 *
 * @param array $p_params
 *
 * @return string $html
 *      The requested calendar as HTML
 *
 * @example
 *  {{ date_filter rangestart="2013-10-30" rangeend="2014-10-30" rangeformatmonth="m" rangeformatday="d" }}
 *
 */

function smarty_function_date_filter($p_params = array(), &$p_smarty)
{
    // The $p_params override the $_GET
    $acceptedParams = array('rangestart', 'rangeend', 'rangeformat', 'rangeformatmonth', 'rangeformatday');
    $cleanParam = array();

    foreach ($acceptedParams as $key) {
        if (array_key_exists($key, $p_params) && !empty($p_params[$key])) {
            $cleanParam[$key] = $p_params[$key];
        } else if (array_key_exists($key, $_GET) && !empty($_GET[$key])) {
            $cleanParam[$key] = $_GET[$key];
        }
    }

    $cleanParam['rangeformatmonth'] = (!array_key_exists('rangeformatmonth', $cleanParam)) ? 'm' : $cleanParam['rangeformatmonth'];
    $cleanParam['rangeformatday'] = (!array_key_exists('rangeformatday', $cleanParam)) ? 'd' : $cleanParam['rangeformatday'];

    $cleanParam['rangestart'] = (!array_key_exists('rangestart', $cleanParam)) ? date('Y-m-d', strtotime('-1 month')) : $cleanParam['rangestart'];
    $cleanParam['rangeend'] = (!array_key_exists('rangeend', $cleanParam)) ? date('Y-m-d', time('now')) : $cleanParam['rangeend'];

    $html = '<div id="archive_list">';
    
    $cleanParam['start']['year'] = date('Y', strtotime($cleanParam['rangestart']));
    $cleanParam['start']['month'] = date('m', strtotime($cleanParam['rangestart']));
    $cleanParam['start']['day'] = date('d', strtotime($cleanParam['rangestart']));
    $cleanParam['end']['year'] = date('Y', strtotime($cleanParam['rangeend']));
    $cleanParam['end']['month'] = date('m', strtotime($cleanParam['rangeend']));
    $cleanParam['end']['day'] = date('d', strtotime($cleanParam['rangeend']));

    $html .= '<ol>';

    for ($year=$cleanParam['start']['year']; $year <= $cleanParam['end']['year']; $year++) { 
        $yearString = '<li><h2>'.$year.'</h2>';
        $monthString = '<ol class="y'.$year.'">';

        $month = ($year == $cleanParam['start']['year']) ? $cleanParam['start']['month'] : 1;
        $endmonth = ($year == $cleanParam['end']['year']) ? $cleanParam['end']['month'] : 12;

        for ($month; $month <= $endmonth; $month++) {
            $monthString .= '<li><h3>'.date($cleanParam['rangeformatmonth'], strtotime($year.'-'.$month)).'</h3>';
            $dayString = '<ol class="m'.$month.'">';
            $maxDay = date('t',strtotime($year.'-'.$month));
            $beginDay = 1;
            if ($year == $cleanParam['start']['year'] && $month == $cleanParam['start']['month'] && $beginDay <= $cleanParam['start']['day']) {
                $beginDay = $cleanParam['start']['day'];
            }
            if ($year == $cleanParam['end']['year'] && $month == $cleanParam['end']['month'] && $maxDay >= $cleanParam['end']['day']) {
                $maxDay = $cleanParam['end']['day'];
            }
            for ($day=$beginDay; $day <= $maxDay; $day++) { 
                $currIterateDate = $year.'-'.$month.'-'.$day;
                $dayString .= '<li';
                $week = 'w'.date('W', strtotime($currIterateDate));
                $dayoftheweek = 'd'.date('N', strtotime($currIterateDate));
                $dayString .= ' class="'.$week.' '.$dayoftheweek.'"';
                $dayString .= '>'. date($cleanParam['rangeformatday'], strtotime($currIterateDate)).'</li>';
            }
            $dayString .= '</ol>';
            $monthString .= $dayString;
        }
        $monthString .= '</li></ol>';
        $html .= $yearString."\n".$monthString.'</li>';
    }

    $html .= '</ol></div>';

    return $html;
}