<?php declare(strict_types=1);

namespace Neucore\Entity;

use Neucore\Api;
use Swagger\Annotations as SWG;
use Doctrine\ORM\Mapping as ORM;

/**
 * @SWG\Definition(
 *     definition="CorporationMember",
 *     required={"id", "name"},
 *     description="The player property contains only id and name, character does not contain corporation.",
 *     @SWG\Property(
 *         property="player",
 *         ref="#/definitions/Player"
 *     )
 * )
 * @ORM\Entity
 * @ORM\Table(name="corporation_members")
 */
class CorporationMember implements \JsonSerializable
{
    /**
     * EVE Character ID.
     *
     * @SWG\Property(format="int64")
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue(strategy="NONE")
     * @var integer
     */
    private $id;

    /**
     * EVE Character name.
     *
     * @SWG\Property(type="string")
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string|null
     */
    private $name;

    /**
     * Corporation ticker.
     *
     * @SWG\Property(type="integer")
     * @ORM\Column(type="bigint", name="location_id", nullable=true)
     * @var integer|null
     */
    private $locationId;

    /**
     * @SWG\Property()
     * @ORM\Column(type="datetime", name="logoff_date", nullable=true)
     * @var \DateTime
     */
    private $logoffDate;

    /**
     * @SWG\Property()
     * @ORM\Column(type="datetime", name="logon_date", nullable=true)
     * @var \DateTime
     */
    private $logonDate;

    /**
     * @SWG\Property(type="integer")
     * @ORM\Column(type="bigint", name="ship_type_id", nullable=true)
     * @var integer|null
     */
    private $shipTypeId;

    /**
     * @SWG\Property()
     * @ORM\Column(type="datetime", name="start_date", nullable=true)
     * @var \DateTime
     */
    private $startDate;

    /**
     * @ORM\ManyToOne(targetEntity="Corporation", inversedBy="members")
     * @ORM\JoinColumn(nullable=false)
     * @var Corporation
     */
    private $corporation;

    /**
     * @SWG\Property(ref="#/definitions/Character")
     * @ORM\OneToOne(targetEntity="Character", inversedBy="corporationMember")
     * @var Character|null
     */
    private $character;

    /**
     * {@inheritDoc}
     * @see \JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize($forUser = true)
    {
        $result = [
            'id' => $this->getId(),
            'name' => $this->name,
            'locationId' => $this->locationId,
            'logoffDate' => $this->getLogoffDate() !== null ? $this->getLogoffDate()->format(Api::DATE_FORMAT) : null,
            'logonDate' => $this->getLogonDate() !== null ? $this->getLogonDate()->format(Api::DATE_FORMAT) : null,
            'shipTypeId' => $this->shipTypeId,
            'startDate' => $this->getStartDate() !== null ? $this->getStartDate()->format(Api::DATE_FORMAT) : null,
        ];

        if ($forUser) {
            $result = array_merge($result, [
                'character' => $this->getCharacter() !== null ? $this->getCharacter()->jsonSerialize(false) : null,
                'player' => $this->getCharacter() !== null ?
                    $this->getCharacter()->getPlayer()->jsonSerialize(true) : null,
            ]);
        }

        return $result;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CorporationMember
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        // cast to int because Doctrine creates string for type bigint
        return $this->id !== null ? (int) $this->id : null;
    }

    /**
     * Set name.
     *
     * @param string|null $name
     *
     * @return CorporationMember
     */
    public function setName($name = null)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set locationId.
     *
     * @param int|null $locationId
     *
     * @return CorporationMember
     */
    public function setLocationId($locationId = null)
    {
        $this->locationId = $locationId;

        return $this;
    }

    /**
     * Get locationId.
     *
     * @return int|null
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * Set logoffDate.
     *
     * @param \DateTime $logoffDate
     *
     * @return CorporationMember
     */
    public function setLogoffDate($logoffDate)
    {
        $this->logoffDate = clone $logoffDate;

        return $this;
    }

    /**
     * Get logoffDate.
     *
     * @return \DateTime|null
     */
    public function getLogoffDate()
    {
        return $this->logoffDate;
    }

    /**
     * Set logonDate.
     *
     * @param \DateTime $logonDate
     *
     * @return CorporationMember
     */
    public function setLogonDate($logonDate)
    {
        $this->logonDate = clone $logonDate;

        return $this;
    }

    /**
     * Get logonDate.
     *
     * @return \DateTime|null
     */
    public function getLogonDate()
    {
        return $this->logonDate;
    }

    /**
     * Set shipTypeId.
     *
     * @param int|null $shipTypeId
     *
     * @return CorporationMember
     */
    public function setShipTypeId($shipTypeId = null)
    {
        $this->shipTypeId = $shipTypeId;

        return $this;
    }

    /**
     * Get shipTypeId.
     *
     * @return int|null
     */
    public function getShipTypeId()
    {
        return $this->shipTypeId;
    }

    /**
     * Set startDate.
     *
     * @param \DateTime $startDate
     *
     * @return CorporationMember
     */
    public function setStartDate($startDate)
    {
        $this->startDate = clone $startDate;

        return $this;
    }

    /**
     * Get startDate.
     *
     * @return \DateTime|null
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set corporation.
     *
     * @param Corporation $corporation
     *
     * @return CorporationMember
     */
    public function setCorporation(Corporation $corporation)
    {
        $this->corporation = $corporation;

        return $this;
    }

    /**
     * Get corporation.
     *
     * @return Corporation
     */
    public function getCorporation()
    {
        return $this->corporation;
    }

    /**
     * Set character.
     *
     * @param Character|null $character
     *
     * @return CorporationMember
     */
    public function setCharacter(Character $character = null)
    {
        $this->character = $character;

        return $this;
    }

    /**
     * Get character.
     *
     * @return Character|null
     */
    public function getCharacter()
    {
        return $this->character;
    }
}
