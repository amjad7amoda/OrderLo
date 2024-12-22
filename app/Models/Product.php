<?php

namespace App\Models;


use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'store_id'
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function carts(): BelongsToMany
    {
        return $this->belongsToMany(Cart::class, 'cart_product_pivot')
            ->withPivot('quantity', 'price');
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_product_pivot')
            ->withPivot('quantity', 'price');
    }

    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    public function scopeFilter(QueryBuilder|EloquentBuilder $query, array $filters)
    {
        return $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', "LIKE", "%$search%")
                    ->orWhere('description', "LIKE", "%$search%");
            });
        })->when($filters['max_price'] ?? null, function ($query, $max_price) {
            $query->where('price', '<=', $max_price);
        })->when($filters['min_price'] ?? null, function ($query, $min_price) {
            $query->where('price', '>=', $min_price);
        });
    }

    public function scopeProductImages(QueryBuilder|EloquentBuilder $query)
    {
        return $query->with('images')->get()->map(function ($product) {
            return array_merge($product->toArray(), [
                "images" => $product->images->map(function ($image) {
                    return asset('storage/'.$image->path);
                })
            ]);
        });
    }
}
