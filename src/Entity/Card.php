<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\CardRepository;
use App\Validator\NoHtml;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CardRepository::class)]
#[UniqueEntity(fields: ['title'], message: 'Ce titre de thème existe déjà !')]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection()
    ],
    normalizationContext: ['groups' => ['card:read']]
)]
class Card
{
    //constante pour la pagination des cartes
    public const CARD_PER_PAGE = 15;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['theme:read', 'card:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[NoHtml]
    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    #[Assert\Length(min: 2, max: 255, maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères')]
    #[Groups(['theme:read', 'card:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[NoHtml]
    #[Assert\NotBlank(message: 'Le détail est obligatoire')]
    #[Groups(['theme:read', 'card:read'])]
    private ?string $detail = null;

    #[ORM\ManyToOne(inversedBy: 'cards')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['card:read'])]
    private ?Theme $theme = null;

    /**
     * Images de fond de la carte (relation ManyToMany)
     * @var Collection<int, Media>
     */
    #[ORM\ManyToMany(targetEntity: Media::class, mappedBy: 'cards')]
    #[Groups(['card:read', 'theme:read'])]
    private Collection $medias;

    /**
     * @var Collection<int, MoreInfo>
     */
    #[ORM\OneToMany(targetEntity: MoreInfo::class, mappedBy: 'Card', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['card:read', 'theme:read'])]
    private Collection $moreInfos;

    #[ORM\ManyToOne(targetEntity: Color::class)]
    #[ORM\JoinColumn(name: "text_color_id", nullable: true)]  // ← ENLÈVE unique: true
    #[Groups(['card:read', 'theme:read'])]
    private ?Color $textColor = null;

    #[ORM\ManyToOne(targetEntity: Color::class)]
    #[ORM\JoinColumn(name: "background_color_id", nullable: true)]  // ← ENLÈVE OneToOne + unique: true
    #[Groups(['card:read', 'theme:read'])]
    private ?Color $backgroundColor = null;

    #[ORM\Column(nullable: true)]
    private ?bool $archived = null;

    public function __construct()
    {
        $this->medias = new ArrayCollection();
        $this->moreInfos = new ArrayCollection();
        $this->archived = false;

    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function setDetail(string $detail): static
    {
        $this->detail = $detail;

        return $this;
    }

    public function getTheme(): ?Theme
    {
        return $this->theme;
    }

    public function setTheme(?Theme $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * @return Collection<int, Media>
     */
    #[Groups(['card:read'])]
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function addMedia(Media $media): static
    {
        if (!$this->medias->contains($media)) {
            $this->medias->add($media);
            $media->addCard($this);
        }

        return $this;
    }

    public function removeMedia(Media $media): static
    {
        if ($this->medias->removeElement($media)) {
            $media->removeCard($this);
        }

        return $this;
    }

    /**
     * Retourne TOUS les chemins d'images de fond possibles (tableau)
     * Format: ['/uploads/media/image/1.jpg', '/uploads/media/image/2.png']
     */
    #[Groups(['card:read'])]
    public function getBackgroundImageUrls(): array
    {
        $urls = [];

        foreach ($this->medias as $media) {
            // Filtrer uniquement les images
            if ($media->getType() === 'image') {
                $publicPath = $media->getPublicPath();
                if ($publicPath) {
                    $urls[] = $publicPath;
                }
            }
        }

        return $urls;
    }

    /**
     * @return Collection<int, MoreInfo>
     */
    #[Groups(['card:read'])]
    public function getMoreInfos(): Collection

    {
        return $this->moreInfos;
    }

    public function addMoreInfo(MoreInfo $moreInfo): static
    {
        if (!$this->moreInfos->contains($moreInfo)) {
            $this->moreInfos->add($moreInfo);
            $moreInfo->setCard($this);
        }

        return $this;
    }

    public function removeMoreInfo(MoreInfo $moreInfo): static
    {
        if ($this->moreInfos->removeElement($moreInfo)) {
            if ($moreInfo->getCard() === $this) {
                $moreInfo->setCard(null);
            }
        }

        return $this;
    }

    public function getTextColor(): ?Color
    {
        return $this->textColor;
    }

    public function setTextColor(?Color $textColor): static
    {
        $this->textColor = $textColor;

        return $this;
    }

    public function getBackgroundColor(): ?Color  // ← CORRIGÉ : C majuscule
    {
        return $this->backgroundColor;
    }

    public function setBackgroundColor(?Color $backgroundColor): static  // ← CORRIGÉ : C majuscule
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    public function isArchived(): ?bool
    {
        return $this->archived;
    }

    public function setArchived(?bool $archived): static
    {
        $this->archived = $archived;

        return $this;
    }
}