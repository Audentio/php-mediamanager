<?php

declare(strict_types=1);

namespace Audentio\MediaManager\Providers;

use Audentio\MediaManager\BroadcastStateEnum;
use Audentio\MediaManager\MediaBroadcastDetails;
use Audentio\MediaManager\MediaTypeEnum;
use Audentio\MediaManager\Providers\Traits\UrlRegexTrait;
use Carbon\CarbonImmutable;

class YoutubeProvider extends AbstractProvider
{
    use UrlRegexTrait;

    public function getType(): MediaTypeEnum
    {
        return MediaTypeEnum::VIDEO;
    }

    public function getName(): string
    {
        return $this->getVideoData()['snippet']['title'];
    }

    public function getDescription(): ?string
    {
        return $this->getVideoData()['snippet']['description'] ?? null;
    }

    public function getDuration(): ?\DateInterval
    {
        return new \DateInterval($this->getVideoData()['contentDetails']['duration']);
    }

    public function getThumbnail(): ?string
    {
        return 'https://i.ytimg.com/vi/' . $this->getId() . '/hqdefault.jpg';
    }

    public function getBroadcastDetails(): ?MediaBroadcastDetails
    {
        $videoDetails = $this->getVideoData();
        if (empty($videoDetails['liveStreamingDetails'])) {
            return null;
        }

        $state = match ($videoDetails['snippet']['liveBroadcastContent'] ?? null) {
            'live' => BroadcastStateEnum::LIVE,
            'upcoming' => BroadcastStateEnum::UPCOMING,
            default => BroadcastStateEnum::ENDED,
        };

        $liveDetails = $videoDetails['liveStreamingDetails'];
        $scheduledStart = null;
        if (isset($liveDetails['scheduledStartTime'])) {
            $scheduledStart = CarbonImmutable::parse($liveDetails['scheduledStartTime']);
        }

        $scheduledEnd = null;
        if (isset($liveDetails['scheduledEndTime'])) {
            $scheduledEnd = CarbonImmutable::parse($liveDetails['scheduledEndTime']);
        }

        $actualStart = null;
        if (isset($liveDetails['actualStartTime'])) {
            $actualStart = CarbonImmutable::parse($liveDetails['actualStartTime']);
        }

        $actualEnd = null;
        if (isset($liveDetails['actualEndTime'])) {
            $actualEnd = CarbonImmutable::parse($liveDetails['actualEndTime']);
        }

        return new MediaBroadcastDetails($state, $scheduledStart, $scheduledEnd, $actualStart, $actualEnd);
    }

    public function exists(): bool
    {
        return $this->getVideoData() !== null;
    }

    protected function getVideoData(): ?array
    {
        $url = 'https://www.googleapis.com/youtube/v3/videos?id=' . $this->getId() .
            '&part=contentDetails,snippet,liveStreamingDetails&key=' . $this->getConfig()['api_key'];
        $response = $this->request($url);
        if (!$response) {
            return null;
        }

        return json_decode($response->getContents(), true)['items'][0] ?? null;
    }

    protected function getRegex(): array
    {
        return [
            '#youtube.com/watch\?v=<PH:slug>#i',
            '#youtube.com/watch\?.*&v=<PH:slug>#i',
            '#youtu.be/<PH:slug>#i',
            '#youtube.com/embed/<PH:slug>#i',
            '#youtube.com/live/<PH:slug>#i',
        ];
    }

    protected function getRequiredConfigKeys(): array
    {
        return [
            'api_key',
        ];
    }
}
