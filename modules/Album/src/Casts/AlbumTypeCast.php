<?php

namespace Modules\Album\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Modules\Album\Enum\AlbumType;
use Modules\Album\Models\Album;

class AlbumTypeCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        /** @var Album $model */
        return match (true) {
            $model->isSingle => AlbumType::SINGLE,
            str_ends_with($model->name, ' - EP') => AlbumType::EP,
            str_ends_with($model->name, ' - Single') => AlbumType::SINGLE,
//            $model->isCompilation => AlbumType::COMPILATION,
            default => AlbumType::ALBUM,
        };

    }

    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        return $value;
    }
}
