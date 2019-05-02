<?php
namespace App\Repository;

use App\Entity\PurchaseToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class PurchaseTokenRepository
 */
class PurchaseTokenRepository extends ServiceEntityRepository
{
    /**
     * Constructor
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PurchaseToken::class);
    }

    /**
     * @param $id
     *
     * @return object|PurchaseToken
     */
    public function findByID($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @param string $token
     *
     * @return object|PurchaseToken
     */
    public function findByToken($token)
    {
        return $this->findOneBy(['token' => $token]);
    }
}
