<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\ValidationException;

class StockLedger
{
    public function adjust(Product $product, int $delta, string $type, ?Model $reference = null, ?string $description = null): StockMovement
    {
        if ($delta === 0) {
            throw ValidationException::withMessages(['quantity' => 'El movimiento debe modificar las existencias.']);
        }

        $before = $product->stock;
        $after = $before + $delta;

        if ($after < 0) {
            throw ValidationException::withMessages(['quantity' => "Stock insuficiente para {$product->name}. Disponible: {$before}."]);
        }
        if ($after > 4294967295) {
            throw ValidationException::withMessages(['quantity' => "El ajuste supera el máximo de existencias para {$product->name}."]);
        }

        $product->update(['stock' => $after]);

        $movement = $product->movements()->create([
            'user_id' => Auth::id(),
            'type' => $type,
            'quantity' => $delta,
            'stock_before' => $before,
            'stock_after' => $after,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'description' => $description,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => "stock.{$type}",
            'auditable_type' => $movement->getMorphClass(),
            'auditable_id' => $movement->getKey(),
            'details' => ['product_id' => $product->id, 'delta' => $delta, 'before' => $before, 'after' => $after],
            'ip_address' => Request::ip(),
        ]);

        return $movement;
    }
}
