<?php

namespace App\Entity;

use App\Repository\SeanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SeanceRepository::class)]
class Seance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $heure_debut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $heure_fin = null;

    #[ORM\Column]
    private ?int $price = null;

    #[ORM\ManyToOne(inversedBy: 'seances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Salle $salle = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(inversedBy: 'seance')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Film $film = null;

    /**
     * @var Collection<int, User>
     */

    /**
     * @var Collection<int, reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'seance')]
    private Collection $reservation;

    /**
     * @var Collection<int, cinema>
     */
    #[ORM\ManyToMany(targetEntity: Cinema::class, inversedBy: 'seances')]
    private Collection $cinema;

    public function __construct()
    {
        $this->reservation = new ArrayCollection();
        $this->cinema = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHeureDebut(): ?\DateTimeInterface
    {
        return $this->heure_debut;
    }

    public function setHeureDebut(\DateTimeInterface $heure_debut): static
    {
        $this->heure_debut = $heure_debut;

        return $this;
    }

    public function getHeureFin(): ?\DateTimeInterface
    {
        return $this->heure_fin;
    }

    public function setHeureFin(\DateTimeInterface $heure_fin): static
    {
        $this->heure_fin = $heure_fin;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getSalle(): ?salle
    {
        return $this->salle;
    }

    public function setSalle(?salle $salle): static
    {
        $this->salle = $salle;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getFilm(): ?Film
    {
        return $this->film;
    }

    public function setFilm(?Film $film): static
    {
        $this->film = $film;

        return $this;
    }

    public function toArray(int $cinemaId): array
    {
        $cinemaId = null;
        foreach ($this->cinema as $cinema) {
            if ($cinema->getId() === $cinemaId) {
                $cinemaId = [
                    'id' => $cinema->getId(),
                ];
                break;
            }
        }

        return [
            'id' => $this->id,
            'date' => $this->date->format('d/m/Y'),
            'dateCommande' => $this->date->format('Y-m-d'),
            'salle' => $this->salle?->getId(),
            'qualite' => $this->salle?->getQualite(),
            'heure_debut_seance' => $this->heure_debut->format('H:i'),
            'heure_fin_seance' => $this->heure_fin->format('H:i'),
            'price' => $this->price,
            'cinema_id' => $cinemaId,
        ];
    }

    public function toArraySeance(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->format('d/m/Y'),
            'salle' => $this->salle?->getId(),
            'qualite' => $this->salle?->getQualite(),
            'heure_debut_seance' => $this->heure_debut->format('H:i'),
            'heure_fin_seance' => $this->heure_fin->format('H:i'),
            'price' => $this->price,
        ];
    }

    public function toArrayReservation(): array
    {
        return [
            'date' => $this->date->format('d/m/Y'),
            'reservation' => $this->reservation->count()
        ];
    }

    /**
     * @return Collection<int, reservation>
     */
    public function getReservation(): Collection
    {
        return $this->reservation;
    }

    public function addReservation(reservation $reservation): static
    {
        if (!$this->reservation->contains($reservation)) {
            $this->reservation->add($reservation);
            $reservation->setSeance($this);
        }

        return $this;
    }

    public function removeReservation(reservation $reservation): static
    {
        if ($this->reservation->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getSeance() === $this) {
                $reservation->setSeance(null);
            }
        }

        return $this;
    }

    public function getDuree(): ?string
    {
        // Vérifier que les deux heures sont définies
        if ($this->heure_debut === null || $this->heure_fin === null) {
            return null; // Retourne null si l'une des heures est manquante
        }

        // Calculer la différence entre les deux heures
        $interval = $this->heure_debut->diff($this->heure_fin);

        // Récupérer le nombre total d'heures et de minutes
        $heures = $interval->h; // Nombre d'heures
        $minutes = $interval->i; // Nombre de minutes

        // Construire la chaîne formatée
        $dureeFormatee = '';
        if ($heures > 0) {
            $dureeFormatee .= "{$heures}h ";
        }
        if ($minutes > 0 || $heures === 0) { // Afficher les minutes même si 0h
            $dureeFormatee .= "{$minutes}min";
        }

        return trim($dureeFormatee); // Supprimer les espaces inutiles
    }

    /**
     * @return Collection<int, cinema>
     */
    public function getCinema(): Collection
    {
        return $this->cinema;
    }

    public function addCinema(cinema $cinema): static
    {
        if (!$this->cinema->contains($cinema)) {
            $this->cinema->add($cinema);
        }

        return $this;
    }

    public function removeCinema(cinema $cinema): static
    {
        $this->cinema->removeElement($cinema);

        return $this;
    }
}
