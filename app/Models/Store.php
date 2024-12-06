<?php

namespace App\Models;

use GuzzleHttp\Psr7\Query;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    /** @use HasFactory<\Database\Factories\StoreFactory> */
    use HasFactory;

    protected $fillable = [
      'name',
      'banner'
    ];


    public function products() : HasMany{
        return $this->hasMany(Product::class);
    }


    public function scopeFilter(QueryBuilder | EloquentBuilder $query, array $filters){
        $query->when($filters['search'] ?? null , function($query, $search) {
          $query->where(function($query) use ($search){
            $query->where('name', 'LIKE', "%$search%")
            ->orWhereHas('products', function($query) use ($search){
              $query->where('name', 'LIKE', "%$search%");
            });
          });
        });
    }

}
