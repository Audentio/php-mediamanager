<?php

namespace Audentio\MediaManager\Providers;

use Audentio\MediaManager\Providers\Traits\UrlRegexTrait;

class YoutubeProvider extends AbstractProvider
{
    use UrlRegexTrait;

    public function getName(): string
    {
        return $this->getVideoData()['snippet']['title'];
    }

    public function getDescription(): ?string
    {
        return $this->getVideoData()['snippet']['description'] ?? null;
    }

    public function getDuration(): \DateInterval
    {
        $videoData = $this->getVideoData();
        return new \DateInterval($videoData['contentDetails']['duration']);
    }

    public function getThumbnail(): ?string
    {
        return 'https://i.ytimg.com/vi/' . $this->getId() . '/hqdefault.jpg';
    }

    public function exists(): bool
    {
        $videoData = $this->getVideoData();

        return $videoData !== null;
    }

    protected function getVideoData(): ?array
    {
        $url = 'https://www.googleapis.com/youtube/v3/videos?id=' . $this->getId() .
            '&part=contentDetails,snippet&key=' . $this->getConfig()['api_key'];
        $response = $this->request($url);
        return json_decode($response->getContents(), true)['items'][0] ?? null;
    }

    protected function getRegex(): array
    {
        return [
            '#youtube.com/watch\?v=<PH:slug>#i',
            '#youtube.com/watch\?.*&v=<PH:slug>#i',
            '#youtu.be/<PH:slug>#i',
            '#youtube.com/embed/<PH:slug>#i',
        ];
    }

    protected function getRequiredConfigKeys(): array
    {
        return [
            'api_key',
        ];
    }
}