<?php

namespace App\Entity\Extra;

use Doctrine\ORM\Mapping as ORM;

trait HasParams
{
    /**
     * @ORM\Column(type="json")
     */
    private $params = [];

    public function getParams(): ?array
    {
        return $this->params;
    }

    public function setParams(array $params): self
    {
        $this->params = $params;

        return $this;
    }

    public function getParam($key)
    {
        return isset($this->params[$key]) ? $this->params[$key] : null;
    }

    public function setParam($key, $value): self
    {
        $this->params[$key] = $value;

        return $this;
    }
}