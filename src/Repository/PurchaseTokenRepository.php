<?php
namespace App\Repository;

use App\Entity\PurchaseToken;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Exception;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class PurchaseTokenRepository
 */
class PurchaseTokenRepository extends ServiceEntityRepository
{
    const TOKEN_EXPIRATION = 43200; // 12 hours

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
     * @throws Exception
     */
    public function findByToken($token)
    {
        $purchaseToken = $this->findOneBy(['token' => $token]);
        if (!$purchaseToken) {
            return null;
        }

        $now  = new DateTime();
        $diff = $now->getTimestamp() - $purchaseToken->getDateCreated()->getTimestamp();
        if ($diff > self::TOKEN_EXPIRATION) {
            return null;
        }

        return $purchaseToken;
    }

    /**
     * @return PurchaseToken[]
     */
    public function findByClientFailure()
    {
        return $this->findBy(['isClientFailure' => true]);
    }
}
