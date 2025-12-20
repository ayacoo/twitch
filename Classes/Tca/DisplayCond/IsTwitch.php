<?php

declare(strict_types=1);

namespace Ayacoo\Twitch\Tca\DisplayCond;

class IsTwitch
{
    /**
     * @param array<string,mixed> $parameters
     */
    public function match(array $parameters): bool
    {
        $record = $parameters['record'] ?? [];
        if (!is_array($record)) {
            return false;
        }

        return (!empty($record['twitch_thumbnail'] ?? ''));
    }
}
