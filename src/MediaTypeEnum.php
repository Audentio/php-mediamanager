<?php

declare(strict_types=1);

namespace Audentio\MediaManager;

enum MediaTypeEnum
{
    case VIDEO;
    case PODCAST;
    case PODCAST_EPISODE;
    case ARTIST;
    case ALBUM;
    case PLAYLIST;
    case SONG;
}
