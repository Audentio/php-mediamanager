<?php

namespace Audentio\MediaManager;

use Carbon\CarbonImmutable;

class MediaBroadcastDetails
{
    private BroadcastStateEnum $state;
    private ?CarbonImmutable $scheduledStart;
    private ?CarbonImmutable $scheduledEnd;
    private ?CarbonImmutable $actualStart;
    private ?CarbonImmutable $actualEnd;

    public function getState(): BroadcastStateEnum
    {
        return $this->state;
    }

    public function getScheduledStart(): ?CarbonImmutable
    {
        return $this->scheduledStart;
    }

    public function getScheduledEnd(): ?CarbonImmutable
    {
        return $this->scheduledEnd;
    }

    public function getActualStart(): ?CarbonImmutable
    {
        return $this->actualStart;
    }

    public function getActualEnd(): ?CarbonImmutable
    {
        return $this->actualEnd;
    }

    public function __construct(BroadcastStateEnum $state, ?CarbonImmutable $scheduledStart,
                                ?CarbonImmutable $scheduledEnd, ?CarbonImmutable $actualStart,
                                ?CarbonImmutable $actualEnd)
    {
        $this->state = $state;
        $this->scheduledStart = $scheduledStart;
        $this->scheduledEnd = $scheduledEnd;
        $this->actualStart = $actualStart;
        $this->actualEnd = $actualEnd;
    }
}