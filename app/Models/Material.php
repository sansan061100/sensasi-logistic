<?php

namespace App\Models;

use Helper;
use App\Models\Traits\CUDLogTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory, CUDLogTrait;

    protected $fillable = ['code', 'name', 'tags', 'unit', 'low_qty', 'brand'];
    protected $appends = ['tags', 'qty', 'id_for_human'];
    protected $with = ['monthlyMovements'];

    public function setTagsAttribute(array $tags)
    {
        $this->tags_json = json_encode($tags);
    }

    public function getTagsAttribute()
    {
        return json_decode($this->tags_json) ?? [];
    }

    public function monthlyMovements()
    {
        return $this->hasMany(MaterialMonthlyMovement::class)->orderByDesc('year')->orderByDesc('month');
    }

    public function inDetails()
    {
        return $this->hasMany(MaterialInDetail::class);
    }

    public function getQtyAttribute()
    {
        $qty = 0;

        foreach ($this->monthlyMovements as $monthlyMovement) {
            $qty += $monthlyMovement->in - $monthlyMovement->out;
        }

        return $qty;
    }

    public function getIdForHumanAttribute()
    {
        return "[{$this->code}] {$this->name}" . ($this->brand ? " ({$this->brand})" : null);
    }

    public function getHasChildrenAttribute()
    {
        return $this->monthlyMovements->count() > 0;
    }
}
