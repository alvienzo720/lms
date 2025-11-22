<?php

namespace App\Helpers;

use Bavix\Wallet\Models\Wallet;

class CurrencyHelper
{
    /**
     * Get the currency code for the organization's primary wallet
     */
    public static function getOrgCurrency(): string
    {
        $wallet = Wallet::where('organization_id', auth()->user()->organization_id)
            ->first();
        
        return $wallet->meta['currency'] ?? 'ZMW';
    }
    
    /**
     * Format amount with currency
     */
    public static function formatMoney(float $amount, ?string $currency = null): string
    {
        $currency = $currency ?? self::getOrgCurrency();
        return $currency . ' ' . number_format($amount, 2);
    }
}
