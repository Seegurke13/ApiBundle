<?php


namespace Seegurke13\ApiBundle\Service;


use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use ReflectionException;
use Seegurke13\ApiBundle\Annotation\Api;
use Seegurke13\ApiBundle\Controller\ApiController;
use Symfony\Bundle\FrameworkBundle\Routing\RouteLoaderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteLoader implements RouteLoaderInterface
{
    const SEARCH_ACTION = 'search';
    const GET_ACTION = 'get';
    const UPDATE_ACTION = 'update';
    const LIST_ACTION = 'list';
    const CREATE_ACTION = 'create';
    const DELETE_ACTION = 'delete';

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
            try {
                $annotation = $this->annotationReader->getClassAnnotation(new ReflectionClass($data->getName()), Api::class);
                if ($annotation) {
                    $routes->addCollection($this->getRoutes($data->getName(), $data->namespace));
                }
            } catch (ReflectionException $e) {
            }
        }

        return $routes;
    }

    private function getRoutes(string $class, string $namespace)
    {
        $name = strtolower(substr($class, strlen($namespace . '\\')));
        $prefix = '/'.$name;
        $collection = new RouteCollection();

        $routeName = 'api_'.$name;


        $searchRoute = new Route($prefix.'/search', [
            '_controller' => $this->getController(self::SEARCH_ACTION),
            'classname' => $class,
        ], [], [], '', [], ['GET']);
        $collection->add($routeName. '_search', $searchRoute);

        $routeGet = new Route($prefix.'/{id}', [
            '_controller' => $this->getController(self::GET_ACTION),
            'classname' => $class,
        ], [], [], '', [], ['GET']);
        $collection->add($routeName. '_get', $routeGet);

        $routeCreate = new Route($prefix.'/', [
            '_controller' => $this->getController(self::CREATE_ACTION),
            'classname' => $class,
        ], [], [], '', [], ['POST']);
        $collection->add($routeName. '_create', $routeCreate);

        $routeList = new Route($prefix.'/', [
            '_controller' => $this->getController(self::LIST_ACTION),
            'classname' => $class
        ], [], [], '', [], ['GET']);
        $collection->add($routeName. '_list', $routeList);

        $routeDelete = new Route($prefix.'/{id}', [
            '_controller' => $this->getController(self::DELETE_ACTION),
            'classname' => 'test'
        ], [], [], '', [], ['DELETE']);
        $collection->add($routeName. '_delete', $routeDelete);

        $routeUpdate = new Route($prefix.'/{id}', [
            '_controller' => $this->getController(self::UPDATE_ACTION),
            'classname' => $class,
        ], [], [], '', [], ['PUT']);
        $collection->add($routeName. '_update', $routeUpdate);

        return $collection;
    }

    private function getController(string $action)
    {
        return sprintf('%s::%s', ApiController::class, $action);
    }
}