<?php

namespace App\Entity;

use App\Repository\RestaurantRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: RestaurantRepository::class)]
#[ORM\HasLifecycleCallbacks] // ✅ IMPORTANT
class Restaurant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 36, unique: true)]
    private ?string $uuid = null;

    #[ORM\Column(length: 32)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::JSON)]
    private array $amOpeningTime = [];

    #[ORM\Column(type: Types::JSON)]
    private array $pmOpeningTime = [];

    #[ORM\Column(type: Types::SMALLINT)]
    private int $maxGuest = 0;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    // ✅ RELATIONS

    #[ORM\OneToMany(mappedBy: 'restaurant', targetEntity: Picture::class, orphanRemoval: true)]
    private Collection $pictures;

    #[ORM\OneToMany(mappedBy: 'restaurant', targetEntity: Menu::class, orphanRemoval: true)]
    private Collection $menus;

    #[ORM\OneToMany(mappedBy: 'restaurant', targetEntity: Booking::class, orphanRemoval: true)]
    private Collection $bookings;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'restaurant')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    public function __construct()
    {
        $this->pictures = new ArrayCollection();
        $this->menus = new ArrayCollection();
        $this->bookings = new ArrayCollection();
    }

    // ✅ LIFECYCLE CALLBACKS

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if (!$this->uuid) {
            $this->uuid = Uuid::v4()->toRfc4122();
        }

        if (!$this->createdAt) {
            $this->createdAt = new DateTimeImmutable();
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    // ✅ GETTERS / SETTERS

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    // ❌ On évite de setter le uuid manuellement (optionnel mais propre)
    // public function setUuid(string $uuid): static

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

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getAmOpeningTime(): array
    {
        return $this->amOpeningTime;
    }

    public function setAmOpeningTime(array $amOpeningTime): static
    {
        $this->amOpeningTime = $amOpeningTime;
        return $this;
    }

    public function getPmOpeningTime(): array
    {
        return $this->pmOpeningTime;
    }

    public function setPmOpeningTime(array $pmOpeningTime): static
    {
        $this->pmOpeningTime = $pmOpeningTime;
        return $this;
    }

    public function getMaxGuest(): int
    {
        return $this->maxGuest;
    }

    public function setMaxGuest(int $maxGuest): static
    {
        $this->maxGuest = $maxGuest;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return Collection<int, Picture> */
    public function getPictures(): Collection
    {
        return $this->pictures;
    }

    public function addPicture(Picture $picture): static
    {
        if (!$this->pictures->contains($picture)) {
            $this->pictures->add($picture);
            $picture->setRestaurant($this);
        }
        return $this;
    }

    public function removePicture(Picture $picture): static
    {
        if ($this->pictures->removeElement($picture) && $picture->getRestaurant() === $this) {
            $picture->setRestaurant(null);
        }
        return $this;
    }

    /** @return Collection<int, Menu> */
    public function getMenus(): Collection
    {
        return $this->menus;
    }

    public function addMenu(Menu $menu): static
    {
        if (!$this->menus->contains($menu)) {
            $this->menus->add($menu);
            $menu->setRestaurant($this);
        }
        return $this;
    }

    public function removeMenu(Menu $menu): static
    {
        if ($this->menus->removeElement($menu) && $menu->getRestaurant() === $this) {
            $menu->setRestaurant(null);
        }
        return $this;
    }

    /** @return Collection<int, Booking> */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setRestaurant($this);
        }
        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking) && $booking->getRestaurant() === $this) {
            $booking->setRestaurant(null);
        }
        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): static
    {
        $this->owner = $owner;

        // ✅ synchronisation inverse (très important)
        if ($owner->getRestaurant() !== $this) {
            $owner->setRestaurant($this);
        }

        return $this;
    }
}