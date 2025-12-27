<?php

namespace App\Entity;

use App\Repository\ThemeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;  // ← AJOUTÉ
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ThemeRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'Ce nom de thème existe déjà !')]
class Theme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(min: 2, max: 255, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères')]
    private ?string $name = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateofcreation = null;

    #[ORM\Column(type: 'boolean')]
    private bool $archived = false;

    public function __construct()
    {
        $this->archived = false;
        $this->dateofcreation = new \DateTimeImmutable();
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static  // ← $name minuscule
    {
        $this->name = $name;
        return $this;
    }

    public function getDateofcreation(): ?\DateTimeImmutable  // ← MINUSCULE
    {
        return $this->dateofcreation;
    }
    /**
     * Setter direct : date de création = AUJOURD'HUI
     * Usage : $theme->setDateCreationToday();
     */
    public function setDateCreationToday(): self
    {
        $this->dateofcreation = new \DateTimeImmutable();
        return $this;
    }


    public function isArchived(): bool  // ← bool (pas ?bool)
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): static
    {
        $this->archived = $archived;
        return $this;
    }
}
