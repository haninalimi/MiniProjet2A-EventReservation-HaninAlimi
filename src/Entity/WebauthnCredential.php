<?php

namespace App\Entity;

use App\Repository\WebauthnCredentialRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Webauthn\PublicKeyCredentialSource;

#[ORM\Entity(repositoryClass: WebauthnCredentialRepository::class)]
#[ORM\Table(name: 'webauthn_credential')]
#[ORM\HasLifecycleCallbacks]
class WebauthnCredential
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'webauthnCredentials')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $credentialId;

    #[ORM\Column(type: 'text')]
    private string $credentialData;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $lastUsedAt;

    public function __construct(User $user, string $name)
    {
        $this->id = Uuid::v4();
        $this->user = $user;
        $this->name = $name;
        $this->createdAt = new \DateTimeImmutable();
        $this->lastUsedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getName(): string { return $this->name; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getLastUsedAt(): \DateTimeImmutable { return $this->lastUsedAt; }
    public function getCredentialId(): string { return $this->credentialId; }

    public function getCredentialSource(): PublicKeyCredentialSource
    {
        return PublicKeyCredentialSource::createFromArray(
            json_decode($this->credentialData, true)
        );
    }

    public function setCredentialSource(PublicKeyCredentialSource $source): void
    {
        $this->credentialId = base64_encode($source->publicKeyCredentialId);
        $this->credentialData = json_encode($source->jsonSerialize());
    }

    public function touch(): void
    {
        $this->lastUsedAt = new \DateTimeImmutable();
    }
}