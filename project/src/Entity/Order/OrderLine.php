<?php

namespace App\Entity\Order;

use App\Repository\OrderLineRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderLineRepository::class)]
class OrderLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orderLines')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $Order = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;
    #[ORM\Column]
    private ?float $idProduct = null;

    #[ORM\Column(nullable: true)]
    private ?float $quantity = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $otherInfos = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->Order;
    }

    public function setOrder(?Order $Order): static
    {
        $this->Order = $Order;

        return $this;
    }

    public function getIdProduct(): ?float
    {
        return $this->idProduct;
    }

    public function setIdProduct(float $idProduct): static
    {
        $this->idProduct = $idProduct;

        return $this;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(?float $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getOtherInfos(): ?string
    {
        return $this->otherInfos;
    }

    public function setOtherInfos(?string $otherInfos): static
    {
        $this->otherInfos = $otherInfos;

        return $this;
    }
}
