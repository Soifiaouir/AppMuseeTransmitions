<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ThemeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ThemeRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'Ce nom de thème existe déjà !')]
#[ApiResource(
    operations: [
        new Get(),           // GET /api/themes/{id}
        new GetCollection()  // GET /api/themes
    ],
    normalizationContext: ['groups' => ['theme:read']],
    paginationEnabled: true,
    paginationItemsPerPage: 30
)]class Theme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['theme:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(min: 2, max: 255, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères')]
    #[Groups(['theme:read'])]
    private ?string $name = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['theme:read'])]
    private ?\DateTimeImmutable $dateOfCreation = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['theme:read'])]
    private bool $archived = false;

    /**
     * @var Collection<int, Card>
     */
    #[ORM\OneToMany(targetEntity: Card::class, mappedBy: 'theme', orphanRemoval: true)]
    #[Groups(['theme:read'])]
    private Collection $cards;

    /**
     * @var Collection<int, Color>
     */
    #[ORM\ManyToMany(targetEntity: Color::class, inversedBy: 'themes')]
    #[ORM\JoinTable(name: 'theme_color')]
    #[Groups(['theme:read'])]
    private Collection $colors;

    /**
     * @var Collection<int, Media>
     */
    #[ORM\ManyToMany(targetEntity: Media::class, mappedBy: 'themes')]
    #[Groups(['theme:read'])]
    private Collection $medias;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[Groups(['theme:read'])]
    private ?Media $backgroundImage = null;

    #[ORM\ManyToOne(inversedBy: 'themes')]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->archived = false;
        $this->dateOfCreation = new \DateTimeImmutable();
        $this->cards = new ArrayCollection();
        $this->colors = new ArrayCollection();
        $this->medias = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDateOfCreation(): ?\DateTimeImmutable
    {
        return $this->dateOfCreation;
    }

    public function setDateOfCreation(): self
    {
        $this->dateOfCreation = new \DateTimeImmutable();
        return $this;
    }

    public function isArchived(): bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): static
    {
        $this->archived = $archived;
        return $this;
    }

    /**
     * @return Collection<int, Card>
     */
    public function getCards(): Collection
    {
        return $this->cards;
    }

    public function addCard(Card $card): static
    {
        if (!$this->cards->contains($card)) {
            $this->cards->add($card);
            $card->setTheme($this);
        }

        return $this;
    }

    public function removeCard(Card $card): static
    {
        if ($this->cards->removeElement($card)) {
            if ($card->getTheme() === $this) {
                $card->setTheme(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Color>
     */
    public function getColors(): Collection
    {
        return $this->colors;
    }

    public function addColor(Color $color): static
    {
        if (!$this->colors->contains($color)) {
            $this->colors->add($color);
            $color->addTheme($this);
        }

        return $this;
    }

    public function removeColor(Color $color): static
    {
        if ($this->colors->removeElement($color)) {
            $color->removeTheme($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    /**
     * @return Collection<int, Media>
     */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function addMedia(Media $media): static
    {
        if (!$this->medias->contains($media)) {
            $this->medias->add($media);
            $media->addTheme($this);
        }

        return $this;
    }

    public function removeMedia(Media $media): static
    {
        if ($this->medias->removeElement($media)) {
            $media->removeTheme($this);
        }

        return $this;
    }

    public function getBackgroundImage(): ?Media
    {
        return $this->backgroundImage;
    }

    public function setBackgroundImage(?Media $backgroundImage): static
    {
        $this->backgroundImage = $backgroundImage;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

}