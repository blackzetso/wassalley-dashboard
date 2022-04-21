<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    protected $casts = [
        'tax' => 'float',
        'price' => 'float',
        'status' => 'integer',
        'discount' => 'float',
        'set_menu' => 'integer',
        'amount' => 'integer',
        'unit' => 'string',

        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

//    protected $appends = ['subCategoryName'];

    public function getSubCategoryNameAttribute()
    {
         if ($this->category_ids) {
            $categories = collect(json_decode($this->category_ids, true));
            $subCategory = $categories->firstWhere('position', 2);
            if($subCategory){
                $productSubCategory = Category::find($subCategory['id']);
                return $productSubCategory['name'] ?? null;
            }
            return null;

        }
        return null;
    }

    public function translations()
    {
        return $this->morphMany('App\Model\Translation', 'translationable');
    }

    public function scopeActive($query)
    {
        return $query->where('status', '=', 1);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function rating()
    {
        return $this->hasMany(Review::class)
            ->select(DB::raw('avg(rating) average, product_id'))
            ->groupBy('product_id');
    }

    public function wishlist()
    {
        return $this->hasMany(Wishlist::class)->latest();
    }

    protected static function booted()
    {
        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function ($query) {
                return $query->where('locale', app()->getLocale());
            }]);
        });
    }
}
