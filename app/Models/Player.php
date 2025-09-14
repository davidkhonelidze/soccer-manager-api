<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function transferListings()
    {
        return $this->hasMany(TransferListing::class);
    }

    public function activeTransferListing()
    {
        return $this->hasOne(TransferListing::class)->where('status', 'active');
    }
}
