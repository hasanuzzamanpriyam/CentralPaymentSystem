<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'api_key',
        'webhook_secret',
        'webhook_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * We hide the secrets so they don't accidentally leak to the frontend API responses.
     */
    protected $hidden = [
        'api_key',
        'webhook_secret',
    ];

    /**
     * Use Laravel's built-in hashing cast feature for the api_key so it's one-way hashed.
     * Use encrypted cast for webhook_secret so we can securely retrieve it to sign outgoing webhooks!
     */
    protected function casts(): array
    {
        return [
            'api_key' => 'hashed',
            'webhook_secret' => 'encrypted',
        ];
    }

    /**
     * Get the user that owns the project.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the configured gateways for this project.
     */
    public function gateways()
    {
        return $this->hasMany(ProjectGateway::class);
    }
    
    /**
     * Get the transactions that belong to this project.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
