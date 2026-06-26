<?php

namespace App\Support;

class Money
{
    /**
     * Format an integer rupee amount using Indian digit grouping, e.g. 142700 → ₹1,42,700.
     */
    public static function inr(int $amount): string
    {
        $digits = (string) abs($amount);

        if (strlen($digits) > 3) {
            $last3 = substr($digits, -3);
            $rest = substr($digits, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $digits = $rest.','.$last3;
        }

        return ($amount < 0 ? '-' : '').'₹'.$digits;
    }
}
