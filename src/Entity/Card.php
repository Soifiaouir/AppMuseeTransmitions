<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\CardRepository;
use App\Validator\NoHtml;
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

    #[ORM\Column(length: 255, nullable: true)]
    #[NoHtml]
    #[Assert\Length(min: 2, max: 255, maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères')]
    #[Groups(['theme:read', 'card:read'])]
    private ?string $moreInfoTitle = null;
    #[NoHtml]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['theme:read', 'card:read'])]
    private ?string $moreInfoDetails = null;

    #[ORM\ManyToOne(inversedBy: 'cards')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['card:read'])]
    private ?Theme $theme = null;

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

    public function getMoreInfoTitle(): ?string
    {
        return $this->moreInfoTitle;
    }

    public function setMoreInfoTitle(?string $moreInfoTitle): static
    {
        $this->moreInfoTitle = $moreInfoTitle;

        return $this;
    }

    public function getMoreInfoDetails(): ?string
    {
        return $this->moreInfoDetails;
    }

    public function setMoreInfoDetails(?string $moreInfoDetails): static
    {
        $this->moreInfoDetails = $moreInfoDetails;

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
}
