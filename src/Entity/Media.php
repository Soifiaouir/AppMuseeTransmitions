<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\MediaRepository;
use App\Validator\NoHtml;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection()
    ],
    normalizationContext: ['groups' => ['media:read']]
)]
class Media
{
    public const MEDIA_PER_PAGE = 10;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['theme:read', 'media:read', 'card:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['media:read', 'theme:read', 'card:read'])]
    #[NoHtml]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    #[Groups(['media:read', 'theme:read', 'card:read'])]
    private ?string $type = null;

    #[ORM\Column(name: "userGivenName", length: 255)]
    #[NoHtml]
    #[Groups(['theme:read', 'media:read', 'card:read'])]
    private ?string $userGivenName = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['media:read', 'card:read'])]
    private ?int $size = null;

    #[ORM\Column(length: 255)]
    #[Groups(['media:read', 'theme:read', 'card:read'])]
    private ?string $extensionFile = null;

    #[ORM\Column]
    #[Groups(['media:read', 'card:read'])]
    private ?\DateTimeImmutable $uploadedAt = null;

    /**
     * Cards qui utilisent ce média
     * Media est maintenant le côté INVERSE de la relation
     * @var Collection<int, Card>
     */
    #[ORM\ManyToMany(targetEntity: Card::class, mappedBy: 'medias')]
    #[Groups(['media:read'])]
    private Collection $cards;

    /**
     * @var Collection<int, Theme>
     */
    #[ORM\ManyToMany(targetEntity: Theme::class, inversedBy: 'medias')]
    #[ORM\JoinTable(name: 'media_theme')]
    #[Groups(['media:read'])]
    private Collection $themes;

    #[ORM\Column(nullable: true)]
    private ?bool $archived = null;

    public function __construct()
    {
        $this->cards = new ArrayCollection();
        $this->themes = new ArrayCollection();
        $this->uploadedAt = new \DateTimeImmutable();
        $this->archived = false;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getUserGivenName(): ?string
    {
        return $this->userGivenName;
    }

    public function setUserGivenName(string $userGivenName): static
    {
        $this->userGivenName = $userGivenName;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getExtensionFile(): ?string
    {
        return $this->extensionFile;
    }

    public function setExtensionFile(string $extensionFile): static
    {
        $this->extensionFile = $extensionFile;

        return $this;
    }

    public function getUploadedAt(): ?\DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(\DateTimeImmutable $uploadedAt): static
    {
        $this->uploadedAt = $uploadedAt;

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
            $card->addMedia($this);
        }

        return $this;
    }

    public function removeCard(Card $card): static
    {
        if ($this->cards->removeElement($card)) {
            $card->removeMedia($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Theme>
     */
    public function getThemes(): Collection
    {
        return $this->themes;
    }

    public function addTheme(Theme $theme): static
    {
        if (!$this->themes->contains($theme)) {
            $this->themes->add($theme);
        }

        return $this;
    }

    public function removeTheme(Theme $theme): static
    {
        $this->themes->removeElement($theme);

        return $this;
    }

    /**
     * Retourne le chemin complet du fichier (relatif au dossier media)
     * Ex: "image/123.jpg"
     */
    public function getFilePath(): string
    {
        return $this->type . '/' . $this->name . '.' . $this->extensionFile;
    }

    /**
     * Retourne le nom complet du fichier avec extension
     * Ex: "123.jpg"
     */
    public function getFullFileName(): string
    {
        return $this->name . '.' . $this->extensionFile;
    }

    /**
     * Chemin public pour l'API (ex: /uploads/media/image/123.jpg)
     * Utilisable directement en React: REACT_API_URL + media.publicPath
     */
    #[Groups(['media:read', 'theme:read', 'card:read'])]
    public function getPublicPath(): ?string
    {
        if (!$this->name || !$this->type || !$this->extensionFile) {
            return null;
        }
        return sprintf('/uploads/media/%s/%s.%s', $this->type, $this->name, $this->extensionFile);
    }

    public function isArchived(): ?bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): static
    {
        $this->archived = $archived;

        return $this;
    }
}