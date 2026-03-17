<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'gateway_name',
        'credentials',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     * By using 'encrypted:array', Laravel will automatically encrypt the JSON object
     * containing the gateway secrets (like Stripe Secret Key) before saving to the DB,
     * and decrypt it back to an array when accessed!
     */
    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the project that this gateway configuration belongs to.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
