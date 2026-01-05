<?php

namespace App\Entity;

use App\Repository\MediaRepository;
use App\Validator\NoHtml;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
class Media
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(name: "userGivenName", length: 255)]
    private ?string $userGivenName = null;

    #[ORM\Column(nullable: true)]
    private ?int $size = null;

    #[ORM\Column(length: 255)]
    private ?string $extensionFile = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $uploadedAt = null;

    /**
     * @var Collection<int, Card>
     */
    #[ORM\ManyToMany(targetEntity: Card::class, inversedBy: 'medias')]
    #[ORM\JoinTable(name: 'media_card')]
    private Collection $cards;

    /**
     * @var Collection<int, Theme>
     */
    #[ORM\ManyToMany(targetEntity: Theme::class, inversedBy: 'medias')]
    #[ORM\JoinTable(name: 'media_theme')]
    private Collection $themes;

    public function __construct()
    {
        $this->cards = new ArrayCollection();
        $this->themes = new ArrayCollection();
        $this->uploadedAt = new \DateTimeImmutable();
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
        }

        return $this;
    }

    public function removeCard(Card $card): static
    {
        $this->cards->removeElement($card);

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
     * Retourne le chemin complet du fichier
     */
    public function getFilePath(): string
    {
        return $this->type . '/' . $this->name;
    }

    public function getFullFileName(): string
    {
        return $this->name . '.' . $this->extensionFile;
    }
}
