<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Subject;

final class Address implements \JsonSerializable
{
    /**
     * Address street.
     *
     * @var string
     */
    public $street;

    /**
     * Address zip code.
     *
     * @var string
     */
    public $zip;

    /**
     * Address city name.
     *
     * @var string
     */
    public $city;

    /**
     * Address province.
     *
     * @var string
     */
    public $province;

    /**
     * Address extra informations.
     *
     * @var string
     */
    public $extra;

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'via' => $this->street,
            'cap' => $this->zip,
            'citta' => $this->city,
            'provincia' => $this->province,
            'extra' => $this->extra,
        ];
    }
}
