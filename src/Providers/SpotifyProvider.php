<?php

namespace Audentio\MediaManager\Providers;

use Audentio\MediaManager\Caches\CacheTypeEnum;
use Audentio\MediaManager\MediaTypeEnum;
use Audentio\MediaManager\Providers\Traits\UrlRegexTrait;
use GuzzleHttp\Exception\ClientException;
use function Aws\map;

class SpotifyProvider extends AbstractProvider
{
    use UrlRegexTrait;

    private array $types = [
        'playlist' => MediaTypeEnum::PLAYLIST,
        'artist' => MediaTypeEnum::ARTIST,
        'album' => MediaTypeEnum::ALBUM,
        'track' => MediaTypeEnum::SONG,

        'show' => MediaTypeEnum::PODCAST,
        'episode' => MediaTypeEnum::PODCAST_EPISODE,
    ];

    private MediaTypeEnum $type;

    public function getType(): MediaTypeEnum
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->getMediaData()['name'];
    }

    public function getDescription(): ?string
    {
        return $this->getMediaData()['description'] ?? null;
    }

    public function getDuration(): ?\DateInterval
    {
        $duration = $this->getMediaData()['duration_ms'] ?? null;
        if (!$duration) {
            return null;
        }

        return $this->getDurationFromSeconds((int) floor($duration / 1000));
    }

    public function getThumbnail(): ?string
    {
        return $this->getMediaData()['images'][0]['url'] ?? null;
    }

    public function exists(): bool
    {
        return $this->getMediaData() !== null;
    }

    protected function getMediaData(bool $forceNewAccessToken = false): ?array
    {
        $accessToken = $this->getAccessToken($forceNewAccessToken);
        $apiUrl = match ($this->type) {
            MediaTypeEnum::PLAYLIST => 'https://api.spotify.com/v1/playlists/' . $this->getId(),
            MediaTypeEnum::ARTIST => 'https://api.spotify.com/v1/artists/' . $this->getId(),
            MediaTypeEnum::ALBUM => 'https://api.spotify.com/v1/albums/' . $this->getId(),
            MediaTypeEnum::SONG => 'https://api.spotify.com/v1/tracks/' . $this->getId(),

            MediaTypeEnum::PODCAST => 'https://api.spotify.com/v1/shows/' . $this->getId() . '?market=US',
            MediaTypeEnum::PODCAST_EPISODE => 'https://api.spotify.com/v1/episodes/' . $this->getId() . '?market=US',
        };
        try {
            $response = $this->request($apiUrl, [
                'headers' => [
                    'Authorization' => $accessToken,
                ],
            ], force: $forceNewAccessToken);
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 401) {
                // Invalid access token, attempt to generate a new one, if that hasn't been done already.
                if ($forceNewAccessToken) {
                    return null;
                }
                return $this->getMediaData(true);
            }

            throw $e;
        }
        if (!$response) {
            return null;
        }
        $contents = $response->getContents();
        return json_decode($contents, true);
    }

    protected function getAccessToken(bool $forceNew = false): string
    {
        $cache = $this->mediaManager->getCache(CacheTypeEnum::ACCESS);
        $credentials = base64_encode($this->getConfig()['client_id'] . ':' . $this->getConfig()['client_secret']);
        $accessTokenHash = 'accessToken_spotify_' . md5($credentials);
        if ($cache->exists($accessTokenHash) && !$forceNew) {
            return $cache->get($accessTokenHash);
        }

        $response = $this->request('https://accounts.spotify.com/api/token', [
            'form_params' => [
                'grant_type' => 'client_credentials',
            ],
            'headers' => [
                'Authorization' => 'Basic ' . $credentials,
            ],
        ], 'post', false);
        $data = json_decode($response->getContents(), true);

        $accessToken = $data['token_type'] . ' ' . $data['access_token'];
        $cache->set($accessTokenHash, $accessToken, $data['expires_in'] - 60);

        return $accessToken;
    }

    protected function getRegex(): array
    {
        return [
            '#^https?://open.spotify.com/<PH:slug:type>/<PH:slug>?#i'
        ];
    }

    protected function getRegexIdFromMatches(array $matches): ?string
    {
        if (!isset($matches['type']) || !isset($matches['id']) || !array_key_exists($matches['type'], $this->types)) {
            return null;
        }

        $this->type = $this->types[$matches['type']];
        return $matches['id'];
    }

    protected function getRequiredConfigKeys(): array
    {
        return ['client_id', 'client_secret'];
    }
}