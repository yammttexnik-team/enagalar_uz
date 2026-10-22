<?php

namespace App\Filament\Resources\BonusTransactions\Pages;

use App\Filament\Resources\BonusTransactions\BonusTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListBonusTransactions extends ListRecords
{
    protected static string $resource = BonusTransactionResource::class;
}
