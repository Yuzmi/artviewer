<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class TagRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    public function findOneByName($name) {
        return $this->findOneByHash(hash("sha1", $name));
    }

    public function findForNames(array $names) {
    	$hashes = [];
    	foreach($names as $name) {
    		$hashes[] = hash("sha1", $name);
    	}

    	return $this->createQueryBuilder("t")
    		->where("t.hash IN (:hashes)")
    		->setParameter("hashes", $hashes)
    		->getQuery()->getResult();
    }
}
