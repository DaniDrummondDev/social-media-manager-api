<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\ValueObjects;

enum PaymentMethod: string
{
    case Pix = 'pix';
    case Boleto = 'boleto';
    case BankTransfer = 'bank_transfer';
    case CreditCard = 'credit_card';
    case Other = 'other';
}
