<?php

namespace UnionWorx\LaravelSerializesModelsWithCache\Attributes;

#[\Attribute]
class CacheTTL
{
    public function __construct(public int $ttl)
    {
    }
}
