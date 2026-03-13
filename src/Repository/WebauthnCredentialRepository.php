<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Webauthn\PublicKeyCredentialSource;

class WebauthnCredentialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebauthnCredential::class);
    }

    public function saveCredential(
        User $user,
        PublicKeyCredentialSource $source,
        string $name = 'My Passkey'
    ): WebauthnCredential {
        $credential = new WebauthnCredential($user, $name);
        $credential->setCredentialSource($source);
        $this->getEntityManager()->persist($credential);
        $this->getEntityManager()->flush();
        return $credential;
    }

    public function findByCredentialId(string $credentialId): ?WebauthnCredential
    {
        return $this->findOneBy([
            'credentialId' => base64_encode($credentialId)
        ]);
    }

    public function findAllByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    public function findSourcesByUser(User $user): array
    {
        return array_map(
            fn(WebauthnCredential $c) => $c->getCredentialSource(),
            $this->findAllByUser($user)
        );
    }
}