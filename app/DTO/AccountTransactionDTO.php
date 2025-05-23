<?php

namespace App\DTO;

use App\Enums\TransactionType;



class AccountTransactionDTO
{
    public function __construct(
        public ?int $id,
        public string $date,
        public string $description,
        public string $debit,
        public string $credit,
        public string $balance,
        public ?TransactionType $type,
        public ?string $tableAction,
    ) {}
}
