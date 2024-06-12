<?php

namespace UnionWorx\LaravelSerializesModelsWithCache\Tests\Resources;

use DateInterval;
use DateTimeInterface;
use UnionWorx\LaravelSerializesModelsWithCache\SerializesModelsWithCache;

class ExampleJobWithMethods
{
    use SerializesModelsWithCache;

    public User $theUser;

    public User $otherUser;

    public function __construct(User $user, User $otherUser)
    {
        $this->theUser = $user;
        $this->otherUser = $otherUser;
    }

    public function cacheKey(string $propertyName, mixed $id): ?string
    {
        if ($propertyName === 'otherUser') {
            return 'my_custom_key_' . $id;
        }

        return null;
    }

    public function cacheTTL(): array|DateInterval|DateTimeInterface|int|null
    {
        return [
            'otherUser' => 120
        ];
    }

    public function cacheSkip(): array
    {
        return [
            'theUser'
        ];
    }
}
