<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Document;

final class PaymentMethod implements \JsonSerializable
{
    /**
     * Payment method name.
     *
     * @var string
     */
    public $name;

    /**
     * Payment method title (max 5 lines).
     *
     * @var string
     */
    public $title;

    /**
     * Payment method description (max 5 lines).
     *
     * @var string
     */
    public $description;

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        if (substr_count($this->title ?? '', "\n") > 4) {
            throw new \RuntimeException('Payment method title cannot contain more than 5 lines');
        }

        if (substr_count($this->description ?? '', "\n") > 4) {
            throw new \RuntimeException('Payment method description cannot contain more than 5 lines');
        }

        $ret = ['metodo_pagamento' => $this->name];

        foreach (explode("\n", $this->title ?? '') as $i => $line) {
            $ret['metodo_titolo'.($i + 1)] = $line;
        }

        foreach (explode("\n", $this->description ?? '') as $i => $line) {
            $ret['metodo_desc'.($i + 1)] = $line;
        }

        return $ret;
    }
}
