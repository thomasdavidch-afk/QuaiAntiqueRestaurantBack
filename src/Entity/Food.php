<?php

namespace App\Entity;

use App\Repository\FoodRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: FoodRepository::class)]
class Food
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['food:read', 'category:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 36, unique: true)]
    #[Groups(['food:read', 'category:read', 'menu:read'])]
    private ?string $uuid = null;

    #[ORM\Column(length: 64)]
    #[Groups(['food:read', 'category:read', 'menu:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['food:read', 'category:read', 'menu:read'])]
    private ?string $description = null;

    // ✅ en centimes
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['food:read', 'category:read', 'menu:read'])]
    private int $price = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    // 🔄 Relation ManyToOne (un plat appartient à une seule catégorie)
    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'foods')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['food:read'])]
    private ?Category $category = null;

    // 🍔 ✅ AJOUT : Relation inverse vers Menu (Un plat peut être rattaché à un menu)
    #[ORM\ManyToOne(targetEntity: Menu::class, inversedBy: 'foods')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Menu $menu = null;

    public function __construct()
    {
        $this->uuid = Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
    }

    // =====================
    // GETTERS / SETTERS
    // =====================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // ✅ Catégorie
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    // ✅ Menu
    public function getMenu(): ?Menu
    {
        return $this->menu;
    }

    public function setMenu(?Menu $menu): static
    {
        $this->menu = $menu;
        return $this;
    }
}