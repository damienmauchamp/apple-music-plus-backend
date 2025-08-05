<?php

namespace Modules\Album\Enum;

enum AlbumType: string
{
    case ALBUM = 'album';
    case SINGLE = 'single';
    case EP = 'ep';
    case COMPILATION = 'compilation';

}
