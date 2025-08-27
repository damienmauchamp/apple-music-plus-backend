<?php

namespace App\Services\DeveloperTokenService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeveloperToken extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'token',
        'notes',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('expires_at', function ($builder) {
            $builder->where('expires_at', '>', now());
        });

        static::addGlobalScope('order', function ($builder) {
            $builder
                ->orderBy('created_at', 'desc')
                ->orderBy('expires_at', 'asc');
        });
    }
}
