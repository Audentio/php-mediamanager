<?php

declare(strict_types=1);

namespace Audentio\MediaManager\Providers\Traits;

trait UrlRegexTrait
{
    protected string $regexId;

    public function getId(): string
    {
        return $this->regexId;
    }

    protected function matchesUrl(): bool
    {
        foreach ($this->getRegex() as $regex) {
            $regex = $this->prepareRegex($regex);
            if (preg_match($regex, $this->url, $matches)) {
                $regexId = $this->getRegexIdFromMatches($matches);
                if ($regexId) {
                    $this->regexId = $regexId;
                    return true;
                }
            }
        }

        return false;
    }

    protected function getRegexIdFromMatches(array $matches): ?string
    {
        return $matches['id'];
    }

    protected function prepareRegex(string $regex): string
    {
        $replacements = [
            'slug' => '(?P<id>[^"\'?&;/<>\#\[\]]+)',
            'int' => '(?P<id>[0-9]+)',
            'alphanum' => '(?P<id>[a-z0-9]+)',
        ];

        foreach ($replacements as $key => $value) {
            $regex = preg_replace_callback(
                '/<PH:' . $key . '(:?([0-9a-zA-Z]+)?)>/',
                function ($matches) use ($key, $value) {
                    if (!empty($matches[2])) {
                        $value = str_replace('<id>', '<' . $matches[2] . '>', $value);
                    }
                    return $value;
                },
                $regex
            );
        }

        return $regex;
    }

    abstract protected function getRegex(): array;
}
