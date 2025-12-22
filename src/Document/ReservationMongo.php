<?php

namespace App\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document]
class ReservationMongo
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $film = null;

    #[MongoDB\Field(type: 'date')]
    private ?DateTime $date = null;

    // Getters et Setters...

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getFilm(): ?string
    {
        return $this->film;
    }

    public function setFilm(string $film): self
    {
        $this->film = $film;
        return $this;
    }

    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    public function setDate(DateTime $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->film,
            'date' => $this->date->format('d/m/Y'),
        ];
    }
}
