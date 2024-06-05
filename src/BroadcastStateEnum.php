<?php

declare(strict_types=1);

namespace Audentio\MediaManager;

enum BroadcastStateEnum
{
    case UPCOMING;
    case LIVE;
    case ENDED;
}
