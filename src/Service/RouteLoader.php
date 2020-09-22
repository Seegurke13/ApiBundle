<?php


namespace Seegurke13\ApiBundle\Service;


use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Seegurke13\ApiBundle\Annotation\Api;
use Seegurke13\ApiBundle\Controller\ApiController;
use Symfony\Bundle\FrameworkBundle\Routing\RouteLoaderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteLoader implements RouteLoaderInterface
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var Reader
     */
    private Reader $annotationReader;

    public function __construct(
        EntityManagerInterface $entityManager,
        Reader $annotationReader
    ) {
        $this->entityManager = $entityManager;
        $this->annotationReader = $annotationReader;
    }

    public function __invoke(): RouteCollection
    {
        $routes = new RouteCollection();

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        foreach ($metadata as $data) {
            $annotation = $this->annotationReader->getClassAnnotation(new \ReflectionClass($data->getName()), Api::class);
            if ($annotation) {
                $routes->addCollection($this->getRoutes($data->getName(), $data->namespace));
            }
        }

        return $routes;
    }

    private function getRoutes(string $class, string $namespace)
    {
        $name = strtolower(substr($class, strlen($namespace . '\\')));
        $prefix = '/'.$name;
        $collection = new RouteCollection();


        $searchRoute = new Route($prefix.'/search', [
            '_controller' => ApiController::class . '::search',
            'classname' => $class,
        ], [], [], '', [], ['GET']);
        $collection->add('api_'.$name.'_search', $searchRoute);

        $routeGet = new Route($prefix.'/{id}', [
            '_controller' => ApiController::class . '::get',
            'classname' => $class,
        ], [], [], '', [], ['GET']);
        $collection->add('api_'.$name.'_get', $routeGet);

        $routeCreate = new Route($prefix.'/', [
            '_controller' => ApiController::class . '::create',
            'classname' => $class,
        ], [], [], '', [], ['POST']);
        $collection->add('api_'.$name.'_create', $routeCreate);

        $routeList = new Route($prefix.'/', [
            '_controller' => ApiController::class .  '::list',
            'classname' => $class
        ], [], [], '', [], ['GET']);
        $collection->add('api_'.$name.'_list', $routeList);

        $routeDelete = new Route($prefix.'/{id}', [
            '_controller' => ApiController::class . '::delete',
            'classname' => 'test'
        ], [], [], '', [], ['DELETE']);
        $collection->add('api_'.$name.'_delete', $routeDelete);

        $routeUpdate = new Route($prefix.'/{id}', [
            '_controller' => ApiController::class . '::update',
            'classname' => $class,
        ], [], [], '', [], ['PUT']);
        $collection->add('api_'.$name.'_update', $routeUpdate);

        return $collection;
    }
}