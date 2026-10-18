<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = [
        'reference', 'request_key', 'supplier_id', 'user_id', 'subtotal',
        'tax_amount', 'total', 'status', 'notes', 'received_at',
    ];

    protected function casts(): array
    {
        return ['subtotal' => 'decimal:2', 'tax_amount' => 'decimal:2', 'total' => 'decimal:2', 'received_at' => 'datetime'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
