<?php


namespace Seegurke13\ApiBundle\Controller;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class ApiController
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    public function get(string $classname, int $id)
    {
        return new Response($this->serializer->serialize($this->entityManager->getRepository($classname)->find($id), 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]));
    }

    public function list(string $classname, Request $request)
    {
        $repo = $this->entityManager->getRepository($classname);
        /** @var QueryBuilder $qb */
        $qb = $repo->createQueryBuilder('a');

        if($request->query->has('ordering')) {
            $order = $request->query->has('desc') ? 'DESC': 'ASC';
            $qb->orderBy('a.' .$request->query->get('ordering'), $order);
            $request->query->remove('ordering');
            $request->query->remove('desc');
        }

        if ($request->query->has('p')) {
            $count = $request->query->has('c') ? $request->query->get('c') : 50;
            $qb->setFirstResult(intval($request->query->get('p')) * $count);
            $qb->setMaxResults($count);
            $request->query->remove('p');
            $request->query->remove('c');
        }
        $data = $qb->getQuery()->getResult();

        return new Response($this->serializer->serialize($data, 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]));
    }

    public function create(Request $request, string $classname)
    {
        $entity = $this->serializer->deserialize($request->getContent(), $classname, 'json');
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return new Response();
    }

    public function delete(string $classname, int $id)
    {
        $entity = $this->entityManager->getRepository($classname)->find($id);
        $this->entityManager->remove($entity);

        return new Response();
    }

    public function update(Request $request, string $classname, int $id)
    {
        $entity = $this->entityManager->getRepository($classname)->find($id);
        $this->serializer->deserialize($request->getContent(), $classname, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $entity]);
        $this->entityManager->flush();

        return new Response();
    }

    public function search(string $classname, Request $request)
    {
        /** @var ObjectRepository $repository */
        $repository = $this->entityManager->getRepository($classname);
        /** @var QueryBuilder $qb */
        $qb = $repository->createQueryBuilder('a');

        if($request->query->has('ordering')) {
            $order = $request->query->has('desc') ? 'DESC': 'ASC';
            $qb->orderBy('a.' .$request->query->get('ordering'), $order);
            $request->query->remove('ordering');
            $request->query->remove('desc');
        }

        if ($request->query->has('p')) {
            $count = $request->query->has('c') ? $request->query->get('c') : 50;
            $qb->setFirstResult(intval($request->query->get('p')) * $count);
            $qb->setMaxResults($count);
            $request->query->remove('p');
            $request->query->remove('c');
        }

        if($request->query->has('q') && isset($this->entityManager->getClassMetadata($classname)->fieldMappings['name'])) {
            $qb->where('a.name LIKE :key');
            $qb->setParameter('key','%'.$request->query->get('q'). '%');
        } else {
            foreach ($request->query as $key=>$value) {
                $tmp = 'key_'.$key;
                $qb->andWhere('a.'.$key.' LIKE :'.$tmp.'');
                $qb->setParameter($tmp,'%'.$request->query->get($key). '%');
            }
        }

        return new Response($this->serializer->serialize($qb->getQuery()->getResult(), 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]));
    }
}