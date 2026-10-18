<?php

namespace App\Support;

use InvalidArgumentException;
use Illuminate\Validation\ValidationException;

class Money
{
    public static function cents(string|int|float $amount): int
    {
        $amount = trim((string) $amount);

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
            throw new InvalidArgumentException('El importe debe tener como máximo dos decimales.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public static function decimal(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    public static function tax(int $taxableCents, string $percentage): int
    {
        $rateBasisPoints = self::cents($percentage);
        if ($rateBasisPoints > 0 && $taxableCents > intdiv(PHP_INT_MAX - 5000, $rateBasisPoints)) {
            throw ValidationException::withMessages(['items' => 'El importe es demasiado alto para calcular el impuesto.']);
        }

        $tax = intdiv(($taxableCents * $rateBasisPoints) + 5000, 10000);
        if ($tax > 99999999999999) {
            throw ValidationException::withMessages(['items' => 'El impuesto supera el importe máximo permitido.']);
        }

        return $tax;
    }

    public static function lineTotal(string|int|float $unitPrice, int $quantity): int
    {
        $unitCents = self::cents($unitPrice);
        $maximumCents = 99999999999999;

        if ($quantity < 1 || $unitCents > intdiv($maximumCents, $quantity)) {
            throw ValidationException::withMessages(['items' => 'El importe de una línea supera el máximo permitido.']);
        }

        return $unitCents * $quantity;
    }

    public static function addToTotal(int $totalCents, int $lineCents): int
    {
        if ($lineCents > 99999999999999 - $totalCents) {
            throw ValidationException::withMessages(['items' => 'El total supera el máximo permitido.']);
        }

        return $totalCents + $lineCents;
    }
}
