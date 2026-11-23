<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Caracteristica extends Model
{
    protected $fillable = ['nombre'];

    public function garages(): BelongsToMany
    {
        return $this->belongsToMany(Garage::class);
    }
}
