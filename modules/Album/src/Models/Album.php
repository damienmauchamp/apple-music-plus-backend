<?php

namespace Modules\Album\Models;

use App\Models\User;
use App\Support\Releasable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Album\Enum\AlbumType;
use Modules\Album\Enum\ContentRating;
use Modules\Artist\Models\Artist;
use Modules\Song\Models\Song;

/**
 * @property int     $id
 * @property string  $storeId
 * @property string  $name
 * @property string  $artistName
 * @property string  $artworkUrl
 * @property string  $releaseDate
 * @property string  $contentRating
 * @property int     $trackCount
 * @property boolean $isSingle
 * @property boolean $isCompilation
 * @property boolean $isComplete
 * @property string  $upc
 * @property boolean $custom
 * @property Carbon  $created_at
 * @property Carbon  $updated_at
 */
class Album extends Model
{
    use Releasable;
    use HasFactory;

    protected $fillable = [
        'storeId',
        'name',
        'artistName',
        'artworkUrl',
        'releaseDate',
        'contentRating',
        'trackCount',
        'isSingle',
        'isCompilation',
        'isComplete',
        'upc',
        'custom',
        'disabled',
    ];

    // protected $casts = [
    //     'isSingle' => 'boolean',
    //     'isCompilation' => 'boolean',
    //     'isComplete' => 'boolean',
    //     'custom' => 'boolean',
    //     'disabled' => 'boolean',
    //     'releaseDate' => 'date:Y-m-d',
    // ];

    public function artists(): BelongsToMany
    {
        return $this->belongsToMany(Artist::class);
    }

    public function songs(): HasMany
    {
        // albums.id -> song.albumId
        return $this->hasMany(Song::class, 'albumId', 'storeId');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_albums', 'album_id', 'user_id');
    }

    // Casts

    public function getTypeAttribute(): AlbumType
    {
        if ($this->isSingle) {
            return AlbumType::SINGLE;
        }

        // if ($this->isCompilation) {
        //     return 'compilation';
        // }

        if (str_ends_with($this->name, ' - EP')) {
            return AlbumType::EP;
        }

        if (str_ends_with($this->name, ' - Single')) {
            return AlbumType::SINGLE;
        }

        return AlbumType::ALBUM;
    }

    // Scopes

    // Scopes: type

    public function scopeIsAlbum(Builder $query): Builder
    {
        return $query->where('isSingle', false)
                     ->whereNotLike('name', '% - EP')
                     ->whereNotLike('name', '% - Single');
    }

    public function scopeIsSingle(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('isSingle', true)
              ->orWhereLike('name', '% - Single');
        });
    }

    public function scopeIsEP(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('isSingle', false)
              ->whereLike('name', '% - EP');
        });
    }

    public function scopeIsCompilation(Builder $query): Builder
    {
        return $query->where('isCompilation', true);
    }

    //

    public function scopeIsUpcoming(Builder $query, ?bool $value = null): Builder
    {
        if ($value === null) {
            return $query;
        }

        $today = Carbon::now()->format('Y-m-d');

        return $query->where(function (Builder $q) use ($value, $today) {
            if ($value) {
                $q->where(function ($sub) use ($today) {
                    $sub->where('isSingle', false)
                        ->where(function ($inner) use ($today) {
                            $inner->where('releaseDate', '>', $today)
                                  ->orWhere('isComplete', false);
                        });
                })->orWhere(function ($sub) use ($today) {
                    $sub->where('isSingle', true)
                        ->where('releaseDate', '>', $today);
                });
            } else {
                $q->where(function ($sub) use ($today) {
                    $sub->where('isSingle', false)
                        ->where('releaseDate', '<=', $today)
                        ->where('isComplete', true);
                })->orWhere(function ($sub) use ($today) {
                    $sub->where('isSingle', true)
                        ->where('releaseDate', '<=', $today);
                });
            }
        });
    }

    public function scopeIsUpcomingOg(Builder $query, ?bool $value = null): Builder
    {
        if ($value === null) {
            return $query;
        }

        return $query->where(
                'releaseDate',
                $value ? '>' : '<=',
                Carbon::now()->format('Y-m-d')
            )->orWhere("isComplete", !$value);
    }

    public function getUniqueNameKey(): string
    {
        return sprintf('%s-%s',
           mb_strtolower($this->name),
           $this->artistName
        );
    }

    //

    // public function scopeWithContentRatingDeduplicated(Builder $query, ?string $preferred = null): Builder
    // {
    //     $preferred = $preferred ?? config('music.default_content_rating', ContentRating::EXPLICIT->value);
    //     $fallback = $preferred === ContentRating::EXPLICIT->value ? ContentRating::CLEAN->value : ContentRating::EXPLICIT->value;
    //
    //     return $query->selectRaw('albums.*,
    //     ROW_NUMBER() OVER (
    //         PARTITION BY name, artistName
    //         ORDER BY
    //             CASE contentRating
    //                 WHEN ? THEN 1
    //                 WHEN ? THEN 2
    //                 ELSE 3
    //             END
    //     ) as row_num', [$preferred, $fallback]);
    // }

}
