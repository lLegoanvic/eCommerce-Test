<?php

namespace App\Entity\Order;

use App\Repository\StateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StateRepository::class)]
class State
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'codeState', targetEntity: Order::class)]
    private Collection $codeState;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    public function __construct()
    {
        $this->codeState = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getCodeState(): Collection
    {
        return $this->codeState;
    }

    public function addCodeState(Order $codeState): static
    {
        if (!$this->codeState->contains($codeState)) {
            $this->codeState->add($codeState);
            $codeState->setCodeState($this);
        }

        return $this;
    }

    public function removeCodeState(Order $codeState): static
    {
        if ($this->codeState->removeElement($codeState)) {
            // set the owning side to null (unless already changed)
            if ($codeState->getCodeState() === $this) {
                $codeState->setCodeState(null);
            }
        }

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
