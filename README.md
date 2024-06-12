# Laravel Serializes Models With Cache

[![Latest Version on Packagist](https://img.shields.io/packagist/v/unionworx/laravel-serializes-models-with-cache.svg?style=flat-square)](https://packagist.org/packages/unionworx/laravel-serializes-models-with-cache)
[![Total Downloads](https://img.shields.io/packagist/dt/unionworx/laravel-serializes-models-with-cache.svg?style=flat-square)](https://packagist.org/packages/unionworx/laravel-serializes-models-with-cache)
![GitHub Actions](https://github.com/unionworx/laravel-serializes-models-with-cache/actions/workflows/main.yml/badge.svg)

This package provides a drop-in replacement for Laravel's SerializesModels trait that leverages your application's cache when unserializing models.

## Installation

You can install the package via composer:

```bash
composer require unionworx/laravel-serializes-models-with-cache
```

## Usage

To use the `SerializesModelsWithCache` trait, simply replace Laravel's `SerializesModels` trait with `SerializesModelsWithCache` in your classes:

```php
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use UnionWorx\LaravelSerializesModelsWithCache\SerializesModelsWithCache;
use App\Models\Message;

class SendMessage
{
    use Dispatchable, InteractsWithQueue, SerializesModelsWithCache;
    
    public Message $message;

    public function handle()
    {
        // Your job logic
    }
}
```

Under the hood this uses most of the default behavior of the `SerializesModels` trait, but will attempt to retrieve the model from the cache before querying the database. This uses the [remember](https://laravel.com/docs/11.x/cache#retrieve-store) feature of the Laravel cache to retrieve the model from the cache or uses the default `SerializesModels` behavior and stores the result.

### Attributes

You can further customize the caching behavior using attributes:

- **`CacheKey`**: Define a custom cache key for a specific property.
- **`CacheTTL`**: Set a custom Time-To-Live (TTL) for the cache entry of a property.
- **`CacheSkip`**: Skip caching for a specific property.

```php
use UnionWorx\LaravelSerializesModelsWithCache\Attributes\CacheKey;
use UnionWorx\LaravelSerializesModelsWithCache\Attributes\CacheTTL;
use UnionWorx\LaravelSerializesModelsWithCache\Attributes\CacheSkip;
use App\Models\User;
use App\Models\Message;

class SendMessage
{
    use Dispatchable, InteractsWithQueue, SerializesModelsWithCache;

    #[CacheKey(key: 'custom_key_{id}')]
    #[CacheTTL(ttl: 120)]
    public Message $message;

    #[CacheSkip]
    public User $user;

    public function handle()
    {
        // Your job logic
    }
}
```

### Methods

Alternatively, you can use the `cacheKey`, `cacheTTL`, and `cacheSkip` methods to customize the caching behavior:

```php
use UnionWorx\LaravelSerializesModelsWithCache\Attributes\CacheKey;
use UnionWorx\LaravelSerializesModelsWithCache\Attributes\CacheTTL;
use UnionWorx\LaravelSerializesModelsWithCache\Attributes\CacheSkip;
use App\Models\User;
use App\Models\Message;

class SendMessage
{
    use Dispatchable, InteractsWithQueue, SerializesModelsWithCache;

    public Message $message;

    public User $user;

    public function handle()
    {
        // Your job logic
    }
    
    public function cacheKey(string $propertyName, mixed $id): ?string
    {
        if ($propertyName === 'message') {
            return 'custom_key_' . $id;
        }
    }
    
    public function cacheTTL(): array|DateInterval|DateTimeInterface|int|null
    {
        return [
            'message' => 120,
        ];
    }
    
    public function cacheSkip(): ?array
    {
        return [
            'user',
        ];
    }
}
```

### Cache Prefix

By default, the cache key is generated using the following pattern `model_classname_id`. This means that models used across multiple contexts will share the same cache key. In most cases this would be beneficial however, you can further isolate the cache by adding a `cachePrefix` method to your class. This will apply the prefix to all keys, even if using custom cache keys.

```php
use UnionWorx\LaravelSerializesModelsWithCache\Attributes\CacheKey;
use UnionWorx\LaravelSerializesModelsWithCache\Attributes\CacheTTL;
use UnionWorx\LaravelSerializesModelsWithCache\Attributes\CacheSkip;
use App\Models\Message;

class SendMessage
{
    use Dispatchable, InteractsWithQueue, SerializesModelsWithCache;

    #[CacheKey(key: 'custom_key_{id}')]
    public Message $message;

    public function handle()
    {
        // Your job logic
    }
    
    public function cachePrefix(): ?string
    {
        return get_class($this);
    }
}
```
### Cache Store

By default, the cache store used is the default cache store defined in your Laravel configuration. You can override this by adding a `cacheStoreName` method to your class.

```php
use UnionWorx\LaravelSerializesModelsWithCache\Attributes\CacheKey;
use UnionWorx\LaravelSerializesModelsWithCache\Attributes\CacheTTL;
use UnionWorx\LaravelSerializesModelsWithCache\Attributes\CacheSkip;
use App\Models\Message;

class SendMessage
{
    use Dispatchable, InteractsWithQueue, SerializesModelsWithCache;

    #[CacheKey(key: 'custom_key_{id}')]
    public Message $message;

    public function handle()
    {
        // Your job logic
    }
    
    public function cacheStoreName(): ?string
    {
        return 'file';
    }
}
```

### Testing

You can run the package tests via composer:

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on recent changes.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email chris@unionworx.cloud instead of using the issue tracker.

## Credits

- [Christopher Carranza](https://github.com/ChristopherCarranza)

## License

MIT. Please see [License File](LICENSE.md) for more information.
