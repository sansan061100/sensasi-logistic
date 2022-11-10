<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material_ins extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $connection = "mysql";
    protected $fillable = ['code', 'at', 'type', 'created_by_user_id', 'last_updated_by_user_id', 'note', 'desc', 'history_json'];

    public function details(){
        $this->hasMany(material_in_details::class);
    }
}
