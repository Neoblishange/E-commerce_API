<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\JsonResponse;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?float $totalPrice = null;

    #[ORM\Column(length: 255)]
    private ?string $creationDate = null;

    #[ORM\Column(type: Types::ARRAY)]
    private array $products = [];

    public function __construct($totalPrice, $creationDate, $products)
    {
        $this->totalPrice = $totalPrice;
        $this->creationDate = $creationDate;
        $this->products = $products;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getTotalPrice(): ?float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(float $totalPrice): self
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getCreationDate(): ?string
    {
        return $this->creationDate;
    }

    public function setCreationDate(string $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getProducts(): array
    {
        return $this->products;
    }

    public function setProducts(array $products): self
    {
        $this->products = $products;
        return $this;
    }

    public function addProduct(Product $product): void
    {
        $this->products[] = $product;
    }

    public function removeProduct(Product $productToRemove): void
    {
        foreach ($this->products as $key => $product) {
            if ($product === $productToRemove) {
                unset($this->products[$key]);
                break;
            }
        }
    }

    public function toJson(): JsonResponse
    {
        $data = [
            'id' => $this->getId(),
            'totalPrice' => $this->getTotalPrice(),
            'creationDate' => $this->getCreationDate(),
            'products' => $this->getProducts(),
        ];
        return new JsonResponse($data);
    }
}
