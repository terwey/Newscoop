<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/*

I would suggest:

  1) Redir modes
    * usage of the redir system woulkd be controlled at system preferences:
      not to use / load new content / redirect to new pages

  2) Storage of rules
    * to store actual URIs redirections in a table, since it is more flexible,
      actual checking at a new plugin (Application_Plugin_Redirect)
    * to put optionally some 'leave this type of URI intact' into htaccess,
      to avoid clashes with our URI redirection rules

  3) Matching types
    * start vs. whole URI, needs to be fast, indexable
    * one new URI for 0-to-N old URI forms

  4) Rule import
    * To have a text file import since it should be easier for larger sites
    * May be a UI for editing particular rules

*/

/**
 * Redirect plugin
 */
class Application_Plugin_Redirect extends Zend_Controller_Plugin_Abstract
{

    private function _redirect ($p_url, $p_status) {
        header('Location: ' . $p_url, true, $p_status);
        exit(0);
    }

    private function _findNewURI ($p_uriOld) {

        // example strings below for development & testing purposes
        $old_str_part = '/abcdef/';
        $new_str = '/en/jan2011/politics/101/News-on-general-theory-of-relativity.htm';

        if ($old_str_part == substr($p_uriOld, 0, strlen($old_str_part))) {
            return $new_str;
        }

        return null;
    }

    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        $legacy = SystemPref::Get('LegacyUriUsage');
        if (!in_array($legacy, array('R', 'S'))) {
            return;
        }

        $req_uri = $request->getRequestUri();

        $new_uri = $this->_findNewURI($req_uri);
        if (!empty($new_uri)) {
            $request->setRequestUri($new_uri);
            $request->setControllerName('content');
            $request->setModuleName('');

            if ('R' == $legacy) {
                //$this->_redirect($new_uri, 301); // permanently - production
                $this->_redirect($new_uri, 307); // temporarily - development
            }
        }
    }

    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
    }

    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
    }

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
    }

}
