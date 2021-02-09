<?php

namespace App\Entity\AvailabilityTracking;

use App\Repository\AvailabilityTracking\TrackingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="availability_tracking")
 * @ORM\Entity(repositoryClass=TrackingRepository::class)
 */
class Tracking
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $chatId;

    /**
     * @ORM\Column(type="string", length=500)
     */
    private $link;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $color;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $size;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastTrackedAt;

    /**
     * @ORM\Column(type="integer")
     */
    private $successfulCount;

    public function __construct()
    {
        $this->successfulCount = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChatId(): ?string
    {
        return $this->chatId;
    }

    public function setChatId(string $chatId): self
    {
        $this->chatId = $chatId;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(string $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(string $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getLastTrackedAt(): ?\DateTimeInterface
    {
        return $this->lastTrackedAt;
    }

    public function setLastTrackedAt(?\DateTimeInterface $lastTrackedAt): self
    {
        $this->lastTrackedAt = $lastTrackedAt;

        return $this;
    }

    public function getSuccessfulCount(): ?int
    {
        return $this->successfulCount;
    }

    public function setSuccessfulCount(int $successfulCount): self
    {
        $this->successfulCount = $successfulCount;

        return $this;
    }

    public function successfulCountIncrement(): self
    {
        $this->successfulCount++;

        return $this;
    }
}
