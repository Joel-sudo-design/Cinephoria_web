<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private array $siege_reserve = [];

    #[ORM\ManyToOne(inversedBy: 'reservation')]
    private ?Seance $seance = null;

    #[ORM\ManyToOne(inversedBy: 'reservation')]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 4000, nullable: true)]
    private ?string $qrCode = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cinema $cinema = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSiege(): array
    {
        return $this->siege_reserve;
    }

    public function setSiege(array $siege): void
    {
        $this->siege_reserve = $siege;
    }

    public function getSeance(): ?Seance
    {
        return $this->seance;
    }

    public function setSeance(?Seance $seance): static
    {
        $this->seance = $seance;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'siege_reserve' => $this->siege_reserve,
            'cinema' => $this->cinema->getName(),
        ];
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }


    public function getQrCode(): ?string
    {
        return $this->qrCode;
    }

    public function setQrCode(?string $qrCode): static
    {
        $this->qrCode = $qrCode;

        return $this;
    }

    public function getCinema(): ?cinema
    {
        return $this->cinema;
    }

    public function setCinema(?cinema $cinema): static
    {
        $this->cinema = $cinema;

        return $this;
    }

}
