<?php

namespace App\Entity;

use App\Repository\MenuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MenuRepository::class)]
class Menu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['menu:read'])]
    #[ORM\Column(length: 36, unique: true)]
    private ?string $uuid = null;

    #[Groups(['menu:read'])]
    #[ORM\Column(length: 64)]
    private ?string $title = null;

    #[Groups(['menu:read'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[Groups(['menu:read'])]
    #[ORM\Column(type: Types::INTEGER)]
    private int $price = 0;

    #[Groups(['menu:read'])]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[Groups(['menu:read'])]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'menus')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Restaurant $restaurant = null;

    #[Groups(['menu:read'])]
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'menus')]
    #[ORM\JoinTable(name: 'menu_category')]
    private Collection $categories;

    // ✅ Collection de plats rattachés au menu (exposée aux clients via le groupe menu:read)
    #[Groups(['menu:read'])]
    #[ORM\OneToMany(mappedBy: 'menu', targetEntity: Food::class, orphanRemoval: true)]
    private Collection $foods;

    public function __construct()
    {
        $this->uuid = Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
        $this->categories = new ArrayCollection();
        $this->foods = new ArrayCollection();
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

    public function setDescription(?string $description): static
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

    public function getRestaurant(): ?Restaurant
    {
        return $this->restaurant;
    }

    public function setRestaurant(?Restaurant $restaurant): static
    {
        $this->restaurant = $restaurant;
        return $this;
    }

    // =====================
    // CATEGORIES (MANY TO MANY)
    // =====================

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->addMenu($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        if ($this->categories->removeElement($category)) {
            $category->removeMenu($this);
        }

        return $this;
    }

    // =====================
    // FOODS (ONE TO MANY)
    // =====================

    /**
     * @return Collection<int, Food>
     */
    public function getFoods(): Collection
    {
        return $this->foods;
    }

    public function addFood(Food $food): static
    {
        if (!$this->foods->contains($food)) {
            $this->foods->add($food);
            $food->setMenu($this);
        }

        return $this;
    }

    public function removeFood(Food $food): static
    {
        if ($this->foods->removeElement($food)) {
            if ($food->getMenu() === $this) {
                $food->setMenu(null);
            }
        }

        return $this;
    }
}