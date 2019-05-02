<?php
namespace App\Repository;

use App\Entity\Merchant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class MerchantRepository
 */
class MerchantRepository extends ServiceEntityRepository
{
    /**
     * Constructor
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Merchant::class);
    }

    /**
     * @param $id
     *
     * @return object|Merchant
     */
    public function findByID($id)
    {
        return $this->findOneBy(['id' => $id]);
    }
}
