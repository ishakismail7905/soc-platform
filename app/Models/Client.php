<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * The single client-separation rule (FR-14). Every page that lists
     * client data must go through this scope.
     * null = all clients; [] = none (whereKey([]) matches no rows).
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $ids = $user->accessibleClientIds();

        return $ids === null ? $query : $query->whereKey($ids);
    }
}
