<?php

namespace Symfony\Cmf\Bundle\ResourceRestBundle\Controller;

use Symfony\Cmf\Component\Resource\RepositoryRegistryInterface;
use Symfony\Component\HttpFoundation\Response;
use Hateoas\UrlGenerator\UrlGeneratorInterface;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;

class ResourceController
{
    /**
     * @var RepositoryRegistryInterface
     */
    private $registry;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param RepositoryInterface
     */
    public function __construct(
        SerializerInterface $serializer,
        RepositoryRegistryInterface $registry,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->serializer = $serializer;
        $this->registry = $registry;
        $this->urlGenerator = $urlGenerator;
    }

    public function resourceAction($repositoryName, $path)
    {
        $repository = $this->registry->get($repositoryName);
        $resource = $repository->get('/' . $path);

        $json = $this->serializer->serialize(
            $resource,
            'json',
            SerializationContext::create()->enableMaxDepthChecks()
        );

        return new Response($json);
    }
}