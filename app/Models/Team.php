<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory;

    protected $fillable = ['uuid', 'name', 'country_id', 'balance'];

    protected $casts = [
        'balance' => 'decimal:2',
        'uuid' => 'string',
        'players_sum_value' => 'decimal:2',
    ];

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($team) {
            if (! $team->uuid) {
                $team->uuid = Str::uuid();
            }
        });
    }

    public static function findByUuid(string $uuid): ?self
    {
        return static::where('uuid', $uuid)->first();
    }
}
