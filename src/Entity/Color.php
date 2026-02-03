<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ColorRepository;
use App\Validator\NoHtml;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\Collection;

/**
 * Entité en charge de gérer la liste des couleurs
 */
#[ORM\Entity(repositoryClass: ColorRepository::class)]
#[UniqueEntity(fields: ['colorCode'], message: 'Cette couleur existe déjà !')]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection()
    ],
    normalizationContext: ['groups' => ['color:read']]
)]
class Color
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['theme:read', 'color:read', 'card:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[NoHtml]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Groups(['theme:read', 'color:read', 'card:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le code de couleur est obligatoire')]
    #[NoHtml]
    #[Assert\CssColor(
        formats: [
            Assert\CssColor::HEX_LONG,
            Assert\CssColor::HEX_SHORT,
            Assert\CssColor::BASIC_NAMED_COLORS,
            Assert\CssColor::EXTENDED_NAMED_COLORS
        ],
        message: "La couleur entrée n'est pas valide. Utilisez un code hexadécimal (#RRGGBB) ou un nom de couleur CSS."
    )]
    #[Groups(['theme:read', 'color:read', 'card:read'])]
    private ?string $colorCode = null;
    /**
     * @var Collection<int, Card>
     * Cartes utilisant cette couleur pour le texte
     */
    #[ORM\OneToMany(targetEntity: Card::class, mappedBy: 'textColor')]
    #[Groups(['color:read'])]
    private Collection $cardsWithTextColor;

    /**
     * @var Collection<int, Card>
     * Cartes utilisant cette couleur en fond
     */
    #[ORM\OneToMany(targetEntity: Card::class, mappedBy: 'backgroundColor')]
    #[Groups(['color:read'])]
    private Collection $cardsWithBackgroundColor;
    public function __construct()
    {
        $this->cardsWithTextColor = new ArrayCollection();
        $this->cardsWithBackgroundColor = new ArrayCollection();
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

    public function getColorCode(): ?string
    {
        return $this->colorCode;
    }

    public function setColorCode(string $colorCode): static
    {
        $this->colorCode = $colorCode;
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
