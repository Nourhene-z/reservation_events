<?php

namespace App\Entity;

use App\Repository\PasskeyCredentialRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PasskeyCredentialRepository::class)]
#[ORM\Table(name: 'passkey_credential')]
#[ORM\UniqueConstraint(name: 'uniq_passkey_credential_id', columns: ['credential_id'])]
class PasskeyCredential
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 16)]
    private ?string $userType = null;

    #[ORM\Column(length: 180)]
    private ?string $userIdentifier = null;

    #[ORM\Column(length: 255)]
    private ?string $credentialId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $publicKey = null;

    #[ORM\Column(nullable: true)]
    private ?int $signCount = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $transports = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserType(): ?string
    {
        return $this->userType;
    }

    public function setUserType(string $userType): static
    {
        $this->userType = $userType;

        return $this;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function setUserIdentifier(string $userIdentifier): static
    {
        $this->userIdentifier = $userIdentifier;

        return $this;
    }

    public function getCredentialId(): ?string
    {
        return $this->credentialId;
    }

    public function setCredentialId(string $credentialId): static
    {
        $this->credentialId = $credentialId;

        return $this;
    }

    public function getPublicKey(): ?string
    {
        return $this->publicKey;
    }

    public function setPublicKey(?string $publicKey): static
    {
        $this->publicKey = $publicKey;

        return $this;
    }

    public function getSignCount(): ?int
    {
        return $this->signCount;
    }

    public function setSignCount(?int $signCount): static
    {
        $this->signCount = $signCount;

        return $this;
    }

    public function getTransports(): ?array
    {
        return $this->transports;
    }

    public function setTransports(?array $transports): static
    {
        $this->transports = $transports;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
