<?php

declare(strict_types = 1);

namespace BrighteCapital\Api\Models;

class PaymentMethod
{

    /** @var string **/
    public $id;

    /** @var int User ID **/
    public $userId;

    /** @var string $type Type of payment method */
    public $type;

    /** @var string $token Token from payment gateway **/
    public $token;

    /** @var string $cardHolder Name of card holder **/
    public $cardHolder;

    /** @var string $cardNumber obfuscated card number **/
    public $cardNumber;

    /** @var \DateTime $cardExpiry MM/YY expiry date of card **/
    public $cardExpiry;

    /** @var string $cardType Type of card e.g. VISA, Mastercard, AMEX **/
    public $cardType;

    /** @var string Bank account number */
    public $accountNumber;

    /** @var string Last 4 digits of account Number */
    public $accountLast4;

    /** @var string Bank account name */
    public $accountName;

    /** @var string Bank account BSB number */
    public $accountBsb;

    /** @var string Agreement Text */
    public $agreementText;

    /** @var string $source App that created this method */
    public $source;

}
