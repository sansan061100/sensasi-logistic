<?php

namespace App\Models;

use Helper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductOut extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'mysql';
    protected $fillable = ['code', 'at', 'type', 'created_by_user_id', 'last_updated_by_user_id', 'note'];

    public static function boot()
    {
        parent::boot();

        static::created(function (self $productOut) {
            Helper::logAction('created', $productOut);
        });

        static::updated(function (self $productOut) {
            Helper::logAction('updated', $productOut);
        });
    }

    public function details()
    {
        return $this->hasMany(ProductOutDetail::class);
    }
    
}