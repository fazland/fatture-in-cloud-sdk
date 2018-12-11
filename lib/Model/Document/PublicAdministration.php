<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Document;

/**
 * @property string $type
 * @property string $documentType
 */
final class PublicAdministration
{
    public const PUBLIC_ENTITY = 'PA';
    public const B2B = 'B2B';

    public const DOCUMENT_TYPE_ORDER = 'ordine';
    public const DOCUMENT_TYPE_CONVENTION = 'convenzione';
    public const DOCUMENT_TYPE_CONTRACT = 'contratto';
    public const DOCUMENT_TYPE_ANY = 'nessuno';

    public const COLLECTABILITY_IMMEDIATE = 'I';
    public const COLLECTABILITY_DELAYED = 'D';
    public const COLLECTABILITY_SPLIT_PAYMENT = 'S';
    public const COLLECTABILITY_NOT_SPECIFIED = 'N';

    public const PAYMENT_METHOD_CASH = 'MP01';
    public const PAYMENT_METHOD_CHECK = 'MP02';
    public const PAYMENT_METHOD_CASHIERS_CHECK = 'MP03';
    public const PAYMENT_METHOD_CASH_TO_TREASURY = 'MP04';
    public const PAYMENT_METHOD_BANK_TRANSFER = 'MP05';
    public const PAYMENT_METHOD_PROMISSORY_NOTE = 'MP06';
    public const PAYMENT_METHOD_BANK_BULLETIN = 'MP07';
    public const PAYMENT_METHOD_CREDIT_CARD = 'MP08';
    public const PAYMENT_METHOD_DIRECT_DEBIT = 'MP09';
    public const PAYMENT_METHOD_DIRECT_DEBIT_UTILITIES = 'MP10';
    public const PAYMENT_METHOD_DIRECT_DEBIT_FAST = 'MP11';
    public const PAYMENT_METHOD_BANK_RECEIPT = 'MP12';
    public const PAYMENT_METHOD_MAV = 'MP13';
    public const PAYMENT_METHOD_RECEIPTS_FROM_THE_STATE = 'MP14';
    public const PAYMENT_METHOD_TRANSFER_TO_SPECIAL_ACCOUNTING_ACCOUNTS = 'MP15';
    public const PAYMENT_METHOD_BANK_DOMICILIATION = 'MP16';
    public const PAYMENT_METHOD_POSTAL_DOMICILIATION = 'MP17';

    /**
     * Customer type (Public entity = PA, B2B).
     *
     * @var string
     */
    private $type;

    /**
     * Type of document for this invoice (order, convention, contract, any).
     *
     * @var string
     */
    private $documentType;

    /**
     * The number of the document.
     *
     * @var string
     */
    public $documentNumber;

    /**
     * The date of the document.
     *
     * @var \DateTimeInterface
     */
    public $date;

    /**
     * Codice Unitario Progetto.
     *
     * @var string
     */
    public $cup;

    /**
     * Codice identificativo gara.
     *
     * @var string
     */
    public $cig;

    /**
     * Public administration office code or customer code.
     *
     * @var string
     */
    public $destinationCode;

    /**
     * Certified email address (for B2B customers).
     *
     * @var string
     */
    public $certifiedEmail;

    /**
     * VAT collectability.
     *
     * @var string
     */
    public $vatCollectability;

    /**
     * Payment method (one of the constants in this class).
     *
     * @var string
     */
    public $paymentMethod;

    /**
     * Name of credit institution.
     *
     * @var string
     */
    public $creditInstitution;

    /**
     * IBAN.
     *
     * @var string
     */
    public $iban;

    /**
     * Payee name.
     *
     * @var string
     */
    public $payee;

    /**
     * Status of sending through the TeamSystem FEPA.
     *
     * @var string
     */
    public $tsStatus;

    public function &__get($name)
    {
        switch ($name) {
            case 'type':
            case 'documentType':
                return $this->{$name};

            default:
                throw new \Error('Undefined property "'.$name.'"');
        }
    }

    public function &__set($name, $value)
    {
        switch ($name) {
            case 'type':
                if (null !== $value && self::PUBLIC_ENTITY !== $value && self::B2B !== $value) {
                    throw new \TypeError(sprintf(
                        'type must be one of %s or null. %s passed.',
                        implode(', ', [self::PUBLIC_ENTITY, self::B2B]),
                        (string) $value
                    ));
                }

                return $this->type = $value;

            case 'documentType':
                if (null !== $value &&
                    self::DOCUMENT_TYPE_ORDER !== $value && self::DOCUMENT_TYPE_CONTRACT !== $value &&
                    self::DOCUMENT_TYPE_CONVENTION !== $value && self::DOCUMENT_TYPE_ANY
                ) {
                    throw new \TypeError(sprintf(
                        'type must be one of %s or null. %s passed.',
                        implode(', ', [self::DOCUMENT_TYPE_ORDER, self::DOCUMENT_TYPE_CONTRACT, self::DOCUMENT_TYPE_CONVENTION, self::DOCUMENT_TYPE_ANY]),
                        (string) $value
                    ));
                }

                return $this->documentType = $value;

            default:
                throw new \Error('Undefined property "'.$name.'"');
        }
    }
}
