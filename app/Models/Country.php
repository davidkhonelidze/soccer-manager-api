<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Country extends Model
{
    use HasTranslations;

    protected $table = 'countries';

    protected $guarded = [];

    public $translatable = ['name'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
