<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Foto extends Model
{
    protected $fillable = ['ruta'];

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    // URL pública del archivo.
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => Storage::disk('public')->url($this->ruta)
        );
    }
}
