<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 36, unique: true)]
    #[Groups(['category:read', 'menu:read'])]
    private ?string $uuid = null;

    #[ORM\Column(length: 64)]
    #[Groups(['category:read', 'menu:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    // 🔄 Relation OneToMany avec Food (un plat n'a qu'une seule catégorie)
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Food::class, orphanRemoval: true)]
    #[Groups(['category:read'])] // Permet d'embarquer la liste des plats quand on récupère la catégorie
    private Collection $foods;

    // Relation inverse avec Menu (inchangée)
    #[ORM\ManyToMany(targetEntity: Menu::class, mappedBy: 'categories')]
    private Collection $menus;

    public function __construct()
    {
        $this->uuid = Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
        $this->foods = new ArrayCollection();
        $this->menus = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
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

    /**
     * @return Collection<int, Food>
     */
    public function getFoods(): Collection
    {
        return $this->foods;
    }

    // ✅ Modifié pour la relation One-To-Many
    public function addFood(Food $food): static
    {
        if (!$this->foods->contains($food)) {
            $this->foods->add($food);
            $food->setCategory($this); // Associe cette catégorie au plat
        }
        return $this;
    }

    // ✅ Modifié pour la relation One-To-Many
    public function removeFood(Food $food): static
    {
        if ($this->foods->removeElement($food)) {
            // Sécurise la relation en mettant la catégorie à null si elle est retirée
            if ($food->getCategory() === $this) {
                $food->setCategory(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Menu>
     */
    public function getMenus(): Collection
    {
        return $this->menus;
    }
}