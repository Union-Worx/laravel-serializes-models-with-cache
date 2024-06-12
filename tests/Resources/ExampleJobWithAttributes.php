<?php

namespace UnionWorx\LaravelSerializesModelsWithCache\Tests\Resources;

use UnionWorx\LaravelSerializesModelsWithCache\Attributes\CacheKey;
use UnionWorx\LaravelSerializesModelsWithCache\Attributes\CacheSkip;
use UnionWorx\LaravelSerializesModelsWithCache\Attributes\CacheTTL;
use UnionWorx\LaravelSerializesModelsWithCache\SerializesModelsWithCache;

class ExampleJobWithAttributes
{
    use SerializesModelsWithCache;

    #[CacheSkip]
    public User $theUser;

    #[CacheTTL(120), CacheKey('my_custom_key_{id}')]
    public User $otherUser;

    public function __construct(User $user, User $otherUser)
    {
        $this->theUser = $user;
        $this->otherUser = $otherUser;
    }
}

