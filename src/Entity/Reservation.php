<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\OneToMany(mappedBy: 'reservation', targetEntity: Cinema::class)]
    private Collection $cinemas;

    public function __construct()
    {
        $this->cinemas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }


    /**
     * @return Collection
     */
    public function getCinemas(): Collection
    {
        return $this->cinemas;
    }

    public function addCinema(Cinema $cinema): self
    {

            $this->cinemas->add($cinema);
            $cinema->setReservation($this);


        return $this;
    }

    public function removeCinema(Cinema $cinema): self
    {
        if ($this->cinemas->removeElement($cinema)) {
            // set the owning side to null (unless already changed)
            if ($cinema->getReservation() === $this) {
                $cinema->setReservation(null);
            }
        }

        return $this;
    }
}
