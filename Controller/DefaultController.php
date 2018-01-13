<?php

namespace Itis\DoctrineEntityBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * @Route("/{entityName}", name="entityPage")
     */
    public function indexAction($entityName)
    {
        $entityName = strtolower($entityName);
        $entityManager = $this->getDoctrine()->getManager();
        $classes = array();
        $metas = $entityManager->getMetadataFactory()->getAllMetadata();

        $prohibitedEntities = $this->container->getParameter('prohibited_entities');

        foreach ($metas as $meta) {
            $classes[strtolower(substr($meta->getName(), strrpos($meta->getName(), '\\') + 1))] = $meta->getName();
        }

        if (array_key_exists($entityName, $classes) && !in_array(ucfirst($entityName), $prohibitedEntities)) {
            $repository = $entityManager->getRepository($classes[$entityName]);
            $request = Request::createFromGlobals();
            $parameters = $request->query->all();
            $fieldNames = $entityManager->getClassMetadata($classes[$entityName])->getFieldNames();
            $rows = [];
            if (empty($parameters)) {
                $rows = $repository->findAll();
            } else {
                foreach ($parameters as $parameterName => $parameterValue) {
                    if (!in_array($parameterName, $fieldNames)) {
                        throw new HttpException(400, ucfirst($entityName) . ' entity does not have ' . $parameterName . 'field!');
                    }
                }
                $rows = $repository->findBy($parameters);
            }

            $convertedRows = array();

            foreach ($rows as $object) {
                $convertedRows[] = (array)$object;
            }

            $response = new JsonResponse();
            $response->setData($convertedRows);

            return $response;

        }

        throw $this->createNotFoundException(ucfirst($entityName) . ' entity does not exist');
    }

    /**
     * @Route("/{entityName}", name="updateEntity")
     */
    public function updateAction($entityName)
    {

        $entityName = strtolower($entityName);
        $entityManager = $this->getDoctrine()->getManager();
        $classes = array();
        $metas = $entityManager->getMetadataFactory()->getAllMetadata();

        $prohibitedEntities = $this->container->getParameter('prohibited_entities');

        foreach ($metas as $meta) {
            $classes[strtolower(substr($meta->getName(), strrpos($meta->getName(), '\\') + 1))] = $meta->getName();
        }

        if (array_key_exists($entityName, $classes) && !in_array(ucfirst($entityName), $prohibitedEntities)) {

            $repository = $entityManager->getRepository($classes[$entityName]);
            $request = Request::createFromGlobals();
            $content = $request->getContent();
            $decodedContent = json_decode($content, true);
            $fieldNames = $entityManager->getClassMetadata($classes[$entityName])->getFieldNames();
            foreach ($decodedContent as $parameterName => $parameterValue) {
                if (!in_array($parameterName, $fieldNames)) {
                    throw new HttpException(400, ucfirst($entityName) . ' entity does not have ' . $parameterName . ' field!');
                }
            }

            $entity = $repository->find($decodedContent['id']);
            foreach ($decodedContent as $fieldName => $fieldValue) {
                $methodName = 'set' . ucfirst($fieldName);
                if ($methodName != 'setId') {
                    $entity->$methodName($fieldValue);
                }
            }
            $entityManager->merge($entity);
            $entityManager->flush();

            return new Response();

        }

        throw $this->createNotFoundException(ucfirst($entityName) . ' entity does not exist');
    }

    /**
     * @Route("/{entityName}", name="insertEntity")
     */
    public function insertAction($entityName)
    {
        $entityName = strtolower($entityName);
        $entityManager = $this->getDoctrine()->getManager();
        $classes = array();
        $metas = $entityManager->getMetadataFactory()->getAllMetadata();

        $prohibitedEntities = $this->container->getParameter('prohibited_entities');

        foreach ($metas as $meta) {
            $classes[strtolower(substr($meta->getName(), strrpos($meta->getName(), '\\') + 1))] = $meta->getName();
        }

        if (array_key_exists($entityName, $classes) && !in_array(ucfirst($entityName), $prohibitedEntities)) {

            $request = Request::createFromGlobals();
            $content = $request->getContent();
            $decodedContent = json_decode($content, true);
            $fieldNames = $entityManager->getClassMetadata($classes[$entityName])->getFieldNames();
            foreach ($decodedContent as $parameterName => $parameterValue) {
                if (!in_array($parameterName, $fieldNames)) {
                    throw new HttpException(400, ucfirst($entityName) . ' entity does not have ' . $parameterName . ' field!');
                }
            }
            foreach ($fieldNames as $fieldName) {
                if ($fieldName != 'id' && !array_key_exists($fieldName, $decodedContent)) {
                    throw new HttpException(400, ucfirst($entityName) . ' must have ' . $fieldName . ' field!');
                }
            }
            $entityName = $classes[$entityName];
            $entity = new $entityName;
            foreach ($decodedContent as $fieldName => $fieldValue) {
                $methodName = 'set' . ucfirst($fieldName);
                if ($methodName != 'setId') {
                    $entity->$methodName($fieldValue);
                }
            }
            $entityManager->persist($entity);
            $entityManager->flush();

            return new Response();

        }

        throw $this->createNotFoundException(ucfirst($entityName) . ' entity does not exist');
    }

    /**
     * @Route("/{entityName}/{id}", name="deleteEntity")
     */
    public function deleteAction($entityName, $id)
    {
        $entityName = strtolower($entityName);
        $entityManager = $this->getDoctrine()->getManager();
        $classes = array();
        $metas = $entityManager->getMetadataFactory()->getAllMetadata();

        $prohibitedEntities = $this->container->getParameter('prohibited_entities');

        foreach ($metas as $meta) {
            $classes[strtolower(substr($meta->getName(), strrpos($meta->getName(), '\\') + 1))] = $meta->getName();
        }

        if (array_key_exists($entityName, $classes) && !in_array(ucfirst($entityName), $prohibitedEntities)) {
            $repository = $entityManager->getRepository($classes[$entityName]);
            $entity = $repository->find($id);
            if ($entity == null) {
                throw new HttpException(400, ucfirst($entityName) . ' with id ' . $id . ' does not exists!');
            }
            $entityManager->remove($entity);
            $entityManager->flush();

            return new Response();

        }

        throw $this->createNotFoundException(ucfirst($entityName) . ' entity does not exist');
    }

    /**
     * @Route("/entities/list", name="entitiesList")
     */
    public function listAction($entityName)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $classes = array();
        $metas = $entityManager->getMetadataFactory()->getAllMetadata();

        $prohibitedEntities = $this->container->getParameter('prohibited_entities');
        foreach ($prohibitedEntities as $prohibitedEntityK => $prohibitedEntityV) {
            $prohibitedEntities[$prohibitedEntityK] = strtolower($prohibitedEntityV);
        }

        $request = Request::createFromGlobals();

        foreach ($metas as $meta) {
            $nameArray = explode('\\', $meta->getName());
            $className = end($nameArray);
            if (!in_array(strtolower($className), $prohibitedEntities)) {
                $classes[] = $className;
            }

        }

        $response = new JsonResponse();
        $response->setData($classes);

        return $response;

    }
}
