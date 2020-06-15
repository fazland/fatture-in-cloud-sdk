<?php

declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Purchase;

class Payment implements \JsonSerializable
{
    /**
     * @var \DateTimeInterface
     */
    private $expireDate;

    /**
     * @var string
     */
    public $method;

    /**
     * @var string
     */
    public $amount;

    /**
     * @var \DateTimeInterface
     */
    private $paymentDate;

    public function __set($name, $value)
    {
        switch ($name) {
            case 'expireDate':
            case 'paymentDate':
                $value = \str_replace('/', '-', $value);
                $value = new \DateTimeImmutable($value);
                break;
            default:
                throw new \Error('Undefined property "'.$name.'"');
        }

        $accessor = function &() use ($name, $value) {
            $this->$name = $value;

            return $this->$name;
        };
        $return = &$accessor();

        return $return;
    }

    public function jsonSerialize()
    {
        return [
            'data_scadenza' => $this->expireDate->format('d/m/Y'),
            'metodo' => $this->method,
            'importo' => $this->amount,
            'data_saldo' => $this->paymentDate,
        ];
    }

    public function fromArray(array $data)
    {
        $this->__set('expireDate', $data['data_scadenza']);
        $this->method = $data['metodo'];
        $this->amount = $data['importo'];
        $this->__set('paymentDate', $data['data_saldo']);

        return $this;
    }
}
