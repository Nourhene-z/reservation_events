<?php

namespace App\Repository;

use App\Entity\PasskeyCredential;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasskeyCredential>
 */
class PasskeyCredentialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasskeyCredential::class);
    }

    public function findOneByCredentialId(string $credentialId): ?PasskeyCredential
    {
        return $this->findOneBy(['credentialId' => $credentialId]);
    }

    /**
     * @return PasskeyCredential[]
     */
    public function findByUser(string $userType, string $userIdentifier): array
    {
        return $this->findBy([
            'userType' => $userType,
            'userIdentifier' => $userIdentifier,
        ]);
    }
}
