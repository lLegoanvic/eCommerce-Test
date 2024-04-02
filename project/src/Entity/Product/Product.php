<?php

namespace App\Entity\Product;

use App\Entity\Category\Category;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: ProductRepository::class)]

class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::FLOAT, length: 255)]
    private ?float $price = null;

    #[ORM\Column(type: Types::FLOAT, length: 255)]
    #[ORM\JoinColumn(nullable: false)]
    private ?float $stock = null;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductPictures::class,fetch: 'EAGER',cascade:['persist','remove'])]
    private Collection $productPictures;

    private ?ArrayCollection $uploadedFiles;

    private ?ProductPictures $productCover=null;

    public function __construct()
    {
        $this->productPictures = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getStock(): ?float
    {
        return $this->stock;
    }

    public function setStock(float $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    /**
     * @return Collection<int, ProductPictures>
     */
    public function getProductPictures(): Collection
    {
        return $this->productPictures;
    }

    public function addProductPicture(ProductPictures $productPicture): static
    {
        if (!$this->productPictures->contains($productPicture)) {
            $this->productPictures->add($productPicture);
            $productPicture->setProduct($this);
        }

        return $this;
    }

    public function removeProductPicture(ProductPictures $productPicture): static
    {
        // set the owning side to null (unless already changed)
        if ($this->productPictures->removeElement($productPicture) && $productPicture->getProduct() === $this) {
            $productPicture->setProduct(null);
        }

        return $this;
    }

    public function getProductCover(): ?ProductPictures
    {
        return $this->productCover;
    }

    public function setProductCover(?ProductPictures $productCover): void
    {
        $this->productCover = $productCover;
    }

    public function getUploadedFiles(): ?ArrayCollection
    {
        return $this->uploadedFiles;
    }

    public function setUploadedFiles(?ArrayCollection $uploadedFiles): void
    {
        $this->uploadedFiles = $uploadedFiles;
    }


}
