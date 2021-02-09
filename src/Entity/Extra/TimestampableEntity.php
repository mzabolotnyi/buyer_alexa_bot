<?php

namespace App\Entity\Extra;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

trait TimestampableEntity
{
    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $createdAt;

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }
}