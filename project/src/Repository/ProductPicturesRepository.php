<?php

namespace App\Repository;

use App\Entity\Product\ProductPictures;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductPictures>
 *
 * @method ProductPictures|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductPictures|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductPictures[]    findAll()
 * @method ProductPictures[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductPicturesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductPictures::class);
    }


//    /**
//     * @return ProductPictures[] Returns an array of ProductPictures objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ProductPictures
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
