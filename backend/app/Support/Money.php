<?php

namespace App\Support;

class Money
{
    /** 1250000 → "1 250 000 GNF" */
    public static function format(int|float|null $amount, string $currency = 'GNF'): string
    {
        return number_format((float) $amount, 0, ',', ' ').' '.$currency;
    }

    /** 1250000 → "1,25 M" (pour les petites cartes du tableau de bord) */
    public static function short(int|float|null $amount): string
    {
        $a = (float) $amount;
        if ($a >= 1_000_000_000) return number_format($a / 1_000_000_000, 2, ',', ' ').' Md';
        if ($a >= 1_000_000) return number_format($a / 1_000_000, 2, ',', ' ').' M';
        if ($a >= 1_000) return number_format($a / 1_000, 0, ',', ' ').' k';
        return number_format($a, 0, ',', ' ');
    }

    public static function qty(float|int|null $q): string
    {
        $q = (float) $q;
        return fmod($q, 1.0) === 0.0 ? number_format($q, 0, ',', ' ') : rtrim(rtrim(number_format($q, 3, ',', ' '), '0'), ',');
    }
}
