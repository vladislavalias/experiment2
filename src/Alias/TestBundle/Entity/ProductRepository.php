<?php

namespace Alias\TestBundle\Entity;

use Doctrine\ORM\EntityRepository;

class ProductRepository extends EntityRepository
{
  public function findAllByMany($inWhatSearch, $whatSearch)
  {
    return $this->createQueryBuilder('p')
      ->where(sprintf('p.%s IN (:searched)', $inWhatSearch))
      ->setParameter(':searched', $whatSearch)
      ->getQuery()
      ->getResult();
  }
}