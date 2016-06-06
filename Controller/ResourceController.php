<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2015 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\ResourceRestBundle\Controller;

use PHPCR\PathNotFoundException;
use PHPCR\Util\PathHelper;
use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Api\ResourceRepository;
use Symfony\Cmf\Component\Resource\RepositoryRegistryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class ResourceController
{
    /**
     * @var RepositoryRegistryInterface
     */
    private $registry;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param SerializerInterface         $serializer
     * @param RepositoryRegistryInterface $registry
     */
    public function __construct(
        SerializerInterface $serializer,
        RepositoryRegistryInterface $registry
    ) {
        $this->serializer = $serializer;
        $this->registry = $registry;
    }

    public function getResourceAction($repositoryName, $path)
    {
        $repository = $this->registry->get($repositoryName);
        $resource = $repository->get('/'.$path);

        return $this->createResponse($resource);
    }

    /**
     * There are two different changes that can currently be done on a cmf resource:
     *
     * - move
     * - rename
     *
     * changing payload properties isn't supported yet.
     *
     * @param string $repositoryName
     * @param string $path
     * @param Request $request
     *
     * @return Response
     */
    public function patchResourceAction($repositoryName, $path, Request $request)
    {
        $repository = $this->registry->get($repositoryName);
        $this->failOnNotEditable($repository, $repositoryName);

        $path = '/'.ltrim($path, '/');

        $requestContent = json_decode($request->getContent(), true);
        if (!$requestContent) {
            return $this->badRequestResponse('Only JSON request bodies are supported.');
        }

        foreach ($requestContent as $action) {
            switch ($action['operation']) {
                case 'move':
                    $targetPath = $action['target'];
                    $repository->move($path, $targetPath);

                    break;
                default:
                    return $this->badRequestResponse('Only move operation is supported.');
            }
        }

        return new Response();
    }

    /**
     * Delete a resource of a repository.
     *
     * @param string $repositoryName
     * @param string $path
     *
     * @return Response
     */
    public function deleteResourceAction($repositoryName, $path)
    {
        $repository = $this->registry->get($repositoryName);
        $this->failOnNotEditable($repository, $repositoryName);

        try {
            $repository->remove($path);
        } catch (\InvalidArgumentException $e) {
            return $this->badRequestResponse($e->getMessage());
        }

        return $this->createResponse('', Response::HTTP_NO_CONTENT);
    }

    private function failOnNotEditable(ResourceRepository $repository, $repositoryName)
    {
        if (!$repository instanceof EditableRepository) {
            throw new RouteNotFoundException(sprintf('Repository %s is not editable.', $repositoryName));
        }
    }

    /**
     * @param string $message
     *
     * @return Response
     */
    private function badRequestResponse($message)
    {
        return $this->createResponse(['message' => $message], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param object|array $resource
     * @param int $httpStatusCode
     * @return Response
     */
    private function createResponse($resource, $httpStatusCode = Response::HTTP_OK)
    {
        $context = SerializationContext::create();
        $context->enableMaxDepthChecks();
        $context->setSerializeNull(true);
        $json = $this->serializer->serialize(
            $resource,
            'json',
            $context
        );

        $response = new Response($json, $httpStatusCode);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
