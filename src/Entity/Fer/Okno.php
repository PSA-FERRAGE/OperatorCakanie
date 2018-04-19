<?php

namespace App\Entity\Fer;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="okno")
 */
class Okno
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", options={"unsigned":true})
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Fer\Linka", inversedBy="okna")
     * @ORM\JoinColumn(name="linka_id", referencedColumnName="id", nullable=false)
     *
     * @var Linka
     */
    private $linka;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $mnemo;

    /**
     * @ORM\Column(type="float")
     *
     * @var float
     */
    private $ciel;

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get linka
     *
     * @return Linka
     */
    public function getLinka(): Linka
    {
        return $this->linka;
    }

    /**
     * Get mnemo
     *
     * @return string
     */
    public function getMnemo(): string
    {
        return $this->mnemo;
    }

    /**
     * Get ciel
     *
     * @return float
     */
    public function getCiel(): float
    {
        return $this->ciel;
    }
}
