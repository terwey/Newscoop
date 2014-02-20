<?php
/**
 * @package Newscoop\Gimme
 * @author Paweł Mikołajczuk <pawel.mikolajczuk@sourcefabric.org>
 * @copyright 2012 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace Newscoop\GimmeBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Newscoop\Gimme\Json;

/**
 * Add Access-Control-Allow-Origin header to response.
 */
class AllowOriginListener
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function onResponse(FilterResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        if (!$this->container->hasParameter('newscoop.gimme.allow_origin')) {
            return false;
        }

        $alowedHosts = $this->container->getParameter('newscoop.gimme.allow_origin');

        if (count($alowedHosts) == 0) {
            return false;
        }

        $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, PUT, DELETE, LINK, PATCH, OPTIONS');

        if (in_array('*', $alowedHosts)) {
            $response->headers->set('Access-Control-Allow-Origin', '*');
        } else {
            foreach ($alowedHosts as $host) {
                if ($request->server->get('HTTP_ORIGIN') == $host) {
                    $response->headers->set('Access-Control-Allow-Origin', $host);
                }
            }
        }

        $event->setResponse($response);
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = new Response();

        if ($request->getMethod() == 'OPTIONS') {
            $response->headers->set('Access-Control-Allow-Headers', $request->headers->get('Access-Control-Request-Headers'));
            $event->setResponse($response);
        }
    }
}
