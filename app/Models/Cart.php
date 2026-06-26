<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'session_id',
    ];

    /**
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function total(): int
    {
        return (int) $this->items->sum('subtotal');
    }

    public function itemCount(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }
}
