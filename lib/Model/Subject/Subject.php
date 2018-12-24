<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Subject;

use Fazland\FattureInCloud\Client\ClientInterface;
use Fazland\FattureInCloud\Exception\NotFoundException;
use Fazland\FattureInCloud\Util\Json;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * @property PhoneNumber $phone
 * @property PhoneNumber $fax
 */
abstract class Subject implements \JsonSerializable
{
    /**
     * Resource identifier.
     *
     * @var string
     */
    public $id;

    /**
     * Subject name.
     *
     * @var string
     */
    public $name;

    /**
     * Reference name.
     *
     * @var string
     */
    public $reference;

    /**
     * Address.
     *
     * @var Address
     */
    public $address;

    /**
     * Subject country.
     *
     * @var string
     */
    public $country;

    /**
     * Subject country iso.
     *
     * @var string
     */
    public $countryIso;

    /**
     * Subject email address.
     *
     * @var string
     */
    public $mail;

    /**
     * Subject phone number.
     *
     * @var PhoneNumber
     */
    private $phone;

    /**
     * Subject fax number.
     *
     * @var PhoneNumber
     */
    private $fax;

    /**
     * Subject VAT number.
     *
     * @var string
     */
    public $vatNumber;

    /**
     * Subject fiscal code.
     *
     * @var string
     */
    public $fiscalCode;

    /**
     * Subject extra info.
     *
     * @var string
     */
    public $extra;

    /**
     * The client used to retrieve this object.
     *
     * @var ClientInterface
     */
    private $client;

    /**
     * The original data, as fetched from the APIs.
     *
     * @var array
     */
    private $originalData;

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        switch ($name) {
            case 'phone':
                return $this->phone ?
                    PhoneNumberUtil::getInstance()->format($this->phone, PhoneNumberFormat::E164) :
                    null;

            case 'fax':
                return $this->fax ?
                    PhoneNumberUtil::getInstance()->format($this->fax, PhoneNumberFormat::E164) :
                    null;

            default:
                throw new \Error('Undefined property "'.$name.'"');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function & __set($name, $value)
    {
        switch ($name) {
            case 'phone':
                $value = $value ?
                    PhoneNumberUtil::getInstance()->parse($value, 'IT') :
                    null;
                break;

            case 'fax':
                $value = $value ?
                    PhoneNumberUtil::getInstance()->parse($value, 'IT') :
                    null;
                break;

            default:
                throw new \Error('Undefined property "'.$name.'"');
        }

        $accessor = function & () use ($name, $value) {
            return $this->$name = $value;
        };
        $return = & $accessor();

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function __isset($name): bool
    {
        return isset($this->$name);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $address = $this->address->jsonSerialize();
        $address = \array_combine(
            \array_map(function (string $key): string { return 'indirizzo_'.$key; }, \array_keys($address)),
            \array_values($address)
        );

        $phoneUtil = PhoneNumberUtil::getInstance();

        return \array_filter([
            'id' => $this->id,
            'nome' => $this->name,
            'referente' => $this->reference,
            'paese' => $this->country,
            'paese_iso' => $this->countryIso,
            'mail' => $this->mail,
            'tel' => $this->phone ? $phoneUtil->format($this->phone, PhoneNumberFormat::E164) : null,
            'fax' => $this->fax ? $phoneUtil->format($this->fax, PhoneNumberFormat::E164) : null,
            'piva' => $this->vatNumber,
            'cf' => $this->fiscalCode,
            'extra' => $this->extra,
        ] + $address);
    }

    /**
     * Fetches a subject from the API.
     *
     * @param string $id
     * @param ClientInterface $client
     *
     * @return Subject
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public static function get(string $id, ClientInterface $client): self
    {
        $obj = new static();
        $obj->client = $client;

        $type = $obj instanceof Supplier ? 'fornitori' : 'clienti';
        $path = $type.'/lista';

        $response = $client->request('POST', $path, [
            'id' => $id,
        ]);

        $result = Json::decode((string) $response->getBody(), true);
        $list = $result['lista_'.$type];

        if (empty($list)) {
            throw new NotFoundException('Resource id #'.$id.' has not been found');
        }

        return $obj->fromArray($list[0]);
    }

    /**
     * Creates a new object on the API server.
     *
     * @param ClientInterface $client
     *
     * @return Subject
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function create(ClientInterface $client): self
    {
        $this->client = $client;
        $path = ($this instanceof Supplier ? 'fornitori' : 'clienti').'/nuovo';

        $response = $this->client->request('POST', $path, $this);

        $result = Json::decode((string) $response->getBody(), true);
        $this->id = $result['id'];

        return $this;
    }

    /**
     * Flushes the modifications to the APIs.
     *
     * @return Subject
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function save(): self
    {
        $fields = \json_decode(\json_encode($this), true);
        \ksort($fields);

        $update = \array_map('unserialize', \array_diff_assoc(\array_map('serialize', $fields), \array_map('serialize', $this->originalData)));
        if (0 === count($update)) {
            return $this;
        }
        $update['id'] = $this->id;

        $path = ($this instanceof Supplier ? 'fornitori' : 'clienti').'/modifica';
        $this->client->request('POST', $path, $update);

        return $this;
    }

    /**
     * Request deletion of this subject.
     *
     * @return Subject
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function delete(): self
    {
        $path = ($this instanceof Supplier ? 'fornitori' : 'clienti').'/elimina';
        $this->client->request('POST', $path, [
            'id' => $this->id,
        ]);

        $this->id = null;

        return $this;
    }

    /**
     * Creates a Subject from a response array.
     *
     * @param array $data
     *
     * @return Subject
     */
    public function fromArray(array $data): self
    {
        $this->originalData = $data;
        \ksort($this->originalData);

        $this->id = $data['id'] ?? null;
        $this->name = $data['nome'] ?? null;
        $this->reference = $data['referente'] ?? null;
        $this->country = $data['paese'] ?? null;
        $this->countryIso = $data['paese_iso'] ?? null;
        $this->mail = $data['mail'] ?? null;
        $this->__set('phone', $data['tel'] ?? null);
        $this->__set('fax', $data['tel'] ?? null);
        $this->vatNumber = $data['piva'] ?? null;
        $this->fiscalCode = $data['cf'] ?? null;
        $this->extra = $data['extra'] ?? null;

        $this->address = new Address();
        $this->address->street = $data['indirizzo_via'] ?? null;
        $this->address->zip = $data['indirizzo_cap'] ?? null;
        $this->address->city = $data['indirizzo_citta'] ?? null;
        $this->address->province = $data['indirizzo_provincia'] ?? null;
        $this->address->extra = $data['indirizzo_extra'] ?? null;

        return $this;
    }
}
