<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferListing extends Model
{
    protected $fillable = [
        'player_id',
        'selling_team_id',
        'asking_price',
        'status',
        'unique_key',
    ];

    protected $casts = [
        'asking_price' => 'decimal:2',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function sellingTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'selling_team_id');
    }
}
