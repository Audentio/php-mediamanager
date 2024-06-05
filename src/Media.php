<?php

declare(strict_types=1);

namespace Audentio\MediaManager;

use Audentio\MediaManager\Providers\AbstractProvider;

/**
 * @property-read string $id
 * @property-read string $name
 * @property-read ?string $description
 * @property-read string $url
 * @property-read \DateInterval $duration
 * @property-read ?string $thumbnail
 * @property-read ?MediaBroadcastDetails $broadcastDetails
 */
class Media
{
    private array $attributes = [
        'id',
        'type',
        'name',
        'description',
        'image',
        'thumbnail',
        'url',
        'duration',
        'broadcastDetails',
    ];
    private AbstractProvider $provider;

    public function exists(): bool
    {
        return $this->provider->exists($this);
    }

    public function __get(string $name)
    {
        if (in_array($name, $this->attributes)) {
            $methodName = 'get' . ucfirst($name);
            return $this->provider->$methodName();
        }

        trigger_error('Undefined property: ' . self::class . '::$' . $name, E_USER_WARNING);
        return null;
    }

    public function __construct(AbstractProvider $provider)
    {
        $this->provider = $provider;
    }
}
