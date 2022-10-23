<?php

namespace App\Entity;

use App\Repository\CinemaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CinemaRepository::class)]
class Cinema
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $seatNumber;
    #[ORM\Column]
    private ?int $lineNumber;

    #[ORM\Column]
    private ?int $roomNumber;

    #[ORM\ManyToOne(targetEntity: Reservation::class,inversedBy: 'cinemas')]
    private ?reservation $reservation = null;

    /**
     * @param $roomNumber
     * @param $lineNumber
     * @param $seatNumber
     */
    public function __construct($roomNumber, $lineNumber, $seatNumber)
    {
        $this->roomNumber = $roomNumber;
        $this->lineNumber = $lineNumber;
        $this->seatNumber = $seatNumber;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeatNumber(): ?int
    {
        return $this->seatNumber;
    }

    public function setSeatNumber(int $seatNumber): self
    {
        $this->seatNumber = $seatNumber;

        return $this;
    }

    public function getLineNumber(): ?int
    {
        return $this->lineNumber;
    }

    public function setLineNumber(int $lineNumber): self
    {
        $this->lineNumber = $lineNumber;

        return $this;
    }

    public function getRoomNumber(): ?int
    {
        return $this->roomNumber;
    }

    public function setRoomNumber(int $roomNumber): self
    {
        $this->roomNumber = $roomNumber;

        return $this;
    }

    public function getReservation(): ?reservation
    {
        return $this->reservation;
    }

    public function setReservation(?reservation $reservation): self
    {
        $this->reservation = $reservation;

        return $this;
    }
}
