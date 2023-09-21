<?php

declare(strict_types=1);

namespace Audentio\MediaManager\Providers;

use Audentio\MediaManager\MediaTypeEnum;
use Audentio\MediaManager\Providers\Traits\UrlRegexTrait;

class VimeoProvider extends AbstractProvider
{
    use UrlRegexTrait;

    public function getType(): MediaTypeEnum
    {
        return MediaTypeEnum::VIDEO;
    }

    public function getName(): string
    {
        return $this->getVideoData()['name'];
    }

    public function getDescription(): ?string
    {
        return $this->getVideoData()['description'] ?? null;
    }

    public function getDuration(): \DateInterval
    {
        $videoData = $this->getVideoData();
        return new \DateInterval('PT' . $videoData['duration'] . 'S');
    }

    public function getThumbnail(): ?string
    {
        return $this->getVideoData()['pictures']['sizes'][3]['link'] ?? null;
    }

    public function exists(): bool
    {
        $videoData = $this->getVideoData();

        return $videoData !== null;
    }

    protected function getVideoData(): ?array
    {
        $url = 'https://api.vimeo.com/videos/' . $this->getId() . '?access_token=' . $this->getConfig()['api_key'];
        $response = $this->request($url);

        return json_decode($response->getContents(), true);
    }

    protected function getRegex(): array
    {
        return [
            '#vimeo.com/<PH:int>#'
        ];
    }

    protected function getRequiredConfigKeys(): array
    {
        return [
            'api_key',
        ];
    }
}
