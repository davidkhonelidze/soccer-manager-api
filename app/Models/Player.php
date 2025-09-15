<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'date_of_birth',
        'position',
        'country_id',
        'team_id',
        'value',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'value' => 'decimal:2',
    ];

    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->date_of_birth?->age ?? 0
        );
    }

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
