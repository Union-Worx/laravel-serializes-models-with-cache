<?php

namespace UnionWorx\LaravelSerializesModelsWithCache;

use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Database\ModelIdentifier;
use Illuminate\Queue\SerializesModels as BaseSerializesModels;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use ReflectionProperty;
use UnionWorx\LaravelSerializesModelsWithCache\Attributes\CacheKey;
use UnionWorx\LaravelSerializesModelsWithCache\Attributes\CacheTTL;
use UnionWorx\LaravelSerializesModelsWithCache\Attributes\CacheSkip;

trait SerializesModelsWithCache
{
    use BaseSerializesModels {
        BaseSerializesModels::getRestoredPropertyValue as originalGetRestoredPropertyValue;
    }

    public function __unserialize(array $values)
    {
        $properties = (new ReflectionClass($this))->getProperties();

        $class = get_class($this);

        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $name = $property->getName();

            if ($property->isPrivate()) {
                $name = "\0{$class}\0{$name}";
            } elseif ($property->isProtected()) {
                $name = "\0*\0{$name}";
            }

            if (! array_key_exists($name, $values)) {
                continue;
            }

            $property->setValue(
                $this, $this->getRestoredPropertyValueFor($values[$name], $property)
            );
        }
    }

    public function getRestoredPropertyValue($value)
    {
        return $this->originalGetRestoredPropertyValue($value);
    }

    public function getRestoredPropertyValueFor($value, ReflectionProperty $property)
    {
        if (! $value instanceof ModelIdentifier) {
            return $value;
        }

        $class = $value->class;
        $id = $value->id;

        // Check if the property should be skipped for caching
        if ($this->shouldSkipProperty($property)) {
            return $this->getRestoredPropertyValue($value);
        }

        $cacheKey = $this->getCacheKeyForProperty($property, $class, $id);
        $ttl = $this->getCacheTTLForProperty($property);

        return Cache::store($this->cacheStoreName())->remember($cacheKey, $ttl, function () use ($value) {
            return $this->getRestoredPropertyValue($value);
        });
    }

    public function getCacheKeyForProperty(ReflectionProperty $property, $class, $id)
    {
        // Check for CacheKey attribute
        $customKey = $this->getAttributeValue($property, CacheKey::class, 'key', $id);
        if ($customKey) {
            return $customKey;
        }

        $customKey = $this->cacheKey($property->getName(), $id);
        if ($customKey) {
            return $customKey;
        }

        return $this->getModelCacheKey($class, $id);
    }

    public function getCacheTTLForProperty(ReflectionProperty $property)
    {
        // Check for CacheTTL attribute
        $ttl = $this->getAttributeValue($property, CacheTTL::class, 'ttl');
        if ($ttl !== null) {
            return $ttl;
        }

        $ttl = $this->cacheTTL();
        if (is_array($ttl)) {
            return $ttl[$property->getName()] ?? 60;
        }
    }

    public function shouldSkipProperty(ReflectionProperty $property): bool
    {
        if ($this->hasAttribute($property, CacheSkip::class)) {
            return true;
        }

        $propertiesToSkip = $this->cacheSkip() ?? [];
        return in_array($property->getName(), $propertiesToSkip, true);
    }

    protected function getAttributeValue(ReflectionProperty $property, $attributeClass, $attributeProperty, $id = null)
    {
        $attributes = $property->getAttributes($attributeClass);
        if (!empty($attributes)) {
            $attribute = $attributes[0]->newInstance();
            $value = $attribute->$attributeProperty;
            if ($id !== null && !is_array($id)) {
                $value = str_replace('{id}', $id, $value);
            }
            return $value;
        }
        return null;
    }

    protected function hasAttribute(ReflectionProperty $property, $attributeClass): bool
    {
        return !empty($property->getAttributes($attributeClass));
    }

    protected function getModelCacheKey($class, $id): string
    {
        $id = is_array($id) ? md5(serialize($id)) : $id;
        $customPrefix = $this->cachePrefix();
        $prefix = !empty($customPrefix) ? $customPrefix . '_' : '';
        return $prefix . 'model_' . $class . '_' . $id;
    }

    /**
     * An array of properties to skip for caching
     *
     * @return array|null
     */
    public function cacheSkip(): ?array
    {
        return null;
    }

    /**
     * A custom cache key for a property
     *
     * @param  string  $propertyName
     * @param  mixed  $id
     *
     * @return string|null
     */
    public function cacheKey(string $propertyName, mixed $id): ?string
    {
        return null;
    }

    /**
     * The TTl for the cache. This can be an array of properties and their TTLs or a single TTL.
     *
     * @return array|DateInterval|DateTimeInterface|int|null
     */
    public function cacheTTL(): array|DateInterval|DateTimeInterface|int|null
    {
        return 60;
    }

    /**
     * The cache store name
     *
     * @return string|null
     */
    public function cacheStoreName(): ?string
    {
        return null;
    }

    /**
     * A prefix for all cache keys
     *
     * @return string|null
     */
    public function cachePrefix(): ?string
    {
        return null;
    }
}
