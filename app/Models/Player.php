<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    public function team()
    {
        $this->belongsTo(Team::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
