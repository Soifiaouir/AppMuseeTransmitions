<?php

namespace App\Entity;

use App\Repository\MoreInfoRepository;
use App\Validator\NoHtml;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MoreInfoRepository::class)]
class MoreInfo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['card:read', 'theme:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'moreInfos')]
    #[Groups(['card:read', 'theme:read'])]
    private ?Card $Card = null;

    #[ORM\Column(length: 255)]
    #[NoHtml]
    #[Assert\Length(min: 2, max: 255, maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères')]
    #[Groups(['card:read', 'theme:read'])]
    private ?string $Title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[NoHtml]
    #[Groups(['card:read', 'theme:read'])]
    private ?string $details = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCard(): ?Card
    {
        return $this->Card;
    }

    public function setCard(?Card $Card): static
    {
        $this->Card = $Card;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->Title;
    }

    public function setTitle(string $Title): static
    {
        $this->Title = $Title;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(string $details): static
    {
        $this->details = $details;

        return $this;
    }
}
