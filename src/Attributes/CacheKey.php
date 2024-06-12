<?php

namespace UnionWorx\LaravelSerializesModelsWithCache\Attributes;

#[\Attribute]
class CacheKey
{
    public function __construct(public string $key)
    {
    }
}
