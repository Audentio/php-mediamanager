<?php

namespace Audentio\MediaManager\Providers;

use Audentio\MediaManager\MediaTypeEnum;
use Audentio\MediaManager\Providers\Traits\UrlRegexTrait;

class ApplePodcastProvider extends AbstractProvider
{
    use UrlRegexTrait;

    private $isEpisode = false;

    public function getType(): MediaTypeEnum
    {
        if ($this->isEpisode) {
            return MediaTypeEnum::AUDIO;
        }

        return MediaTypeEnum::GROUP;
    }

    public function getName(): string
    {
        return $this->getPodcastData()['name'];
    }

    public function getDescription(): ?string
    {
        return $this->getPodcastData()['description'];
    }

    public function getDuration(): ?\DateInterval
    {
        $duration = $this->getPodcastData()['duration'] ?? null;
        if (!$duration) {
            return null;
        }

        return new \DateInterval($duration);
    }

    public function getThumbnail(): ?string
    {
        return null;
    }

    public function exists(): bool
    {
        return $this->getPodcastData() !== null;
    }

    protected function getPodcastData(): ?array
    {
        $response = $this->request($this->url);
        $contents = $response->getContents();
        preg_match('#name="schema:podcast-(?:episode)?(?:show)?".*?>(.*?)</script>#is', $contents, $matches);
        return json_decode(trim($matches[1] ?? ''), true);
    }

    protected function getRegex(): array
    {
        return [
            '#^https?://podcasts.apple.com/.*?/podcast/.*?\/id<PH:int:show>(?:\?.*?i=)?<PH:int:episode>?#i'
        ];
    }

    protected function getRegexIdFromMatches(array $matches): string
    {
        if (isset($matches['episode'])) {
            $this->isEpisode = true;
            return $matches['episode'];
        }

        return $matches['show'];
    }
}