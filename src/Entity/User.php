<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Validator\NoHtml;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
#[UniqueEntity(fields: ['username'], message: 'Il y a déjà un utilisateur avec ce pseudo')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Pseudo obligatoire')]
    #[NoHtml]
    private ?string $username = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[NoHtml]
    private ?string $password = null;
    /**
     * @var Collection<int, Theme>
     */
    #[ORM\OneToMany(targetEntity: Theme::class, mappedBy: 'createdBy')]
    private Collection $themes;

    #[ORM\Column(nullable: true)]
    private ?bool $passwordChange = true;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $passwordChangeDate = null;


    public function __construct()
    {
        $this->themes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

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
            $theme->setCreatedBy($this);
        }

        return $this;
    }

    public function removeTheme(Theme $theme): static
    {
        if ($this->themes->removeElement($theme)) {
            // set the owning side to null (unless already changed)
            if ($theme->getCreatedBy() === $this) {
                $theme->setCreatedBy(null);
            }
        }

        return $this;
    }

    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function isPasswordChange(): ?bool
    {
        return $this->passwordChange;
    }

    public function setPasswordChange(?bool $passwordChange): static
    {
        $this->passwordChange = $passwordChange;

        return $this;
    }

    public function getPasswordChangeDate(): ?\DateTime
    {
        return $this->passwordChangeDate;
    }

    public function setPasswordChangeDate(?\DateTime $passwordChangeDate): static
    {
        $this->passwordChangeDate = $passwordChangeDate;

        return $this;
    }

}