<?php

namespace Itis\DoctrineEntityBundle\EventListener;

use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class UrlConflictListener
{
    private $container;
    private $doctrine;

    /**
     * @return mixed
     */
    public function getDoctrine()
    {
        return $this->doctrine;
    }

    /**
     * @param mixed $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @return mixed
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param mixed $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $router = $this->container->get('router');
        $collection = $router->getRouteCollection();
        $allRoutes = $collection->all();

        $entityManager = $this->doctrine->getManager();
        $classes = array();
        $metas = $entityManager->getMetadataFactory()->getAllMetadata();

        $prohibitedEntities = $this->container->getParameter('prohibited_entities');
        foreach ($prohibitedEntities as $prohibitedEntityK => $prohibitedEntityV) {
            $prohibitedEntities[$prohibitedEntityK] = strtolower($prohibitedEntityV);
        }

        foreach ($metas as $meta) {
            $nameArray = explode('\\', $meta->getName());
            $className = end($nameArray);
            if (!in_array(strtolower($className), $prohibitedEntities)) {
                $classes[] = '/' . strtolower($className);
            }
        }

        $urlsWithConflict = "";

        foreach ($allRoutes as $route) {
            if (in_array($route->getPath(), $classes)) {
                $urlsWithConflict .= '\'' . $route->getPath() . '\', ';
            }
        }

        if (!empty($urlsWithConflict)) {
            throw new InternalErrorException('You have conflicts for the following routes ' . substr($urlsWithConflict, 0, -2));
        }

    }
}