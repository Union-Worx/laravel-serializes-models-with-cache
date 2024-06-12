<?php

namespace UnionWorx\LaravelSerializesModelsWithCache\Tests;

use Illuminate\Contracts\Database\ModelIdentifier;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;
use ReflectionProperty;
use UnionWorx\LaravelSerializesModelsWithCache\Tests\Resources\ExampleJobWithAttributes;
use UnionWorx\LaravelSerializesModelsWithCache\Tests\Resources\ExampleJobWithMethods;
use UnionWorx\LaravelSerializesModelsWithCache\Tests\Resources\User;

#[WithMigration]
class SerializesModelsWithCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::shouldReceive('store')->andReturnSelf();
    }

    public function testUnserializeMethod()
    {
        $user = new User(['id' => 1, 'name' => 'John Doe']);
        $user->save();
        $otherUser = new User(['id' => 2, 'name' => 'Jane Doe']);
        $otherUser->save();

        $job = new ExampleJobWithAttributes($user, $otherUser);
        $serialized = serialize($job);
        $unserializedJob = unserialize($serialized);

        $this->assertEquals($user->id, $unserializedJob->theUser->id);
        $this->assertEquals($otherUser->id, $unserializedJob->otherUser->id);
    }

    public function testGetRestoredPropertyValueForWithoutCache()
    {
        $user = new User(['id' => 1, 'name' => 'John Doe']);
        $otherUser = new User(['id' => 2, 'name' => 'Jane Doe']);

        $job = new ExampleJobWithAttributes($user, $otherUser);
        $reflection = new ReflectionProperty($job, 'theUser');
        $reflection->setAccessible(true);

        $result = $job->getRestoredPropertyValueFor(new ModelIdentifier(User::class, 1, [],'database'), $reflection);

        $this->assertEquals($user, $result);
    }

    public function testGetRestoredPropertyValueForWithCache()
    {
        $user = new User(['id' => 1, 'name' => 'John Doe']);
        $otherUser = new User(['id' => 2, 'name' => 'Jane Doe']);

        $job = new ExampleJobWithAttributes($user, $otherUser);
        $reflection = new ReflectionProperty($job, 'otherUser');
        $reflection->setAccessible(true);

        Cache::shouldReceive('remember')
             ->once()
             ->with('my_custom_key_2', 120, \Closure::class)
             ->andReturn($otherUser);

        $result = $job->getRestoredPropertyValueFor(new ModelIdentifier(User::class, 2, [],'database'), $reflection);

        $this->assertEquals($otherUser, $result);
    }

    public function testGetCacheKeyForProperty()
    {
        $user = new User(['id' => 1, 'name' => 'John Doe']);
        $otherUser = new User(['id' => 2, 'name' => 'Jane Doe']);

        $job = new ExampleJobWithAttributes($user, $otherUser);
        $reflection = new ReflectionProperty($job, 'otherUser');
        $reflection->setAccessible(true);

        $cacheKey = $job->getCacheKeyForProperty($reflection, User::class, 2);

        $this->assertEquals('my_custom_key_2', $cacheKey);
    }

    public function testGetCacheTTLForProperty()
    {
        $user = new User(['id' => 1, 'name' => 'John Doe']);
        $otherUser = new User(['id' => 2, 'name' => 'Jane Doe']);

        $job = new ExampleJobWithAttributes($user, $otherUser);
        $reflection = new ReflectionProperty($job, 'otherUser');
        $reflection->setAccessible(true);

        $ttl = $job->getCacheTTLForProperty($reflection);

        $this->assertEquals(120, $ttl);
    }

    public function testShouldSkipProperty()
    {
        $user = new User(['id' => 1, 'name' => 'John Doe']);
        $otherUser = new User(['id' => 2, 'name' => 'Jane Doe']);

        $job = new ExampleJobWithAttributes($user, $otherUser);
        $reflection = new ReflectionProperty($job, 'theUser');
        $reflection->setAccessible(true);

        $shouldSkip = $job->shouldSkipProperty($reflection);

        $this->assertTrue($shouldSkip);
    }

    public function testGetCacheKeyForPropertyUsingMethod()
    {
        $user = new User(['id' => 1, 'name' => 'John Doe']);
        $otherUser = new User(['id' => 2, 'name' => 'Jane Doe']);

        $job = new ExampleJobWithMethods($user, $otherUser);
        $reflection = new ReflectionProperty($job, 'otherUser');
        $reflection->setAccessible(true);

        $cacheKey = $job->getCacheKeyForProperty($reflection, User::class, 2);

        $this->assertEquals('my_custom_key_2', $cacheKey);
    }

    public function testGetCacheTTLForPropertyUsingMethod()
    {
        $user = new User(['id' => 1, 'name' => 'John Doe']);
        $otherUser = new User(['id' => 2, 'name' => 'Jane Doe']);

        $job = new ExampleJobWithMethods($user, $otherUser);
        $reflection = new ReflectionProperty($job, 'otherUser');
        $reflection->setAccessible(true);

        $ttl = $job->getCacheTTLForProperty($reflection);

        $this->assertEquals(120, $ttl);
    }

    public function testShouldSkipPropertyUsingMethod()
    {
        $user = new User(['id' => 1, 'name' => 'John Doe']);
        $otherUser = new User(['id' => 2, 'name' => 'Jane Doe']);

        $job = new ExampleJobWithMethods($user, $otherUser);
        $reflection = new ReflectionProperty($job, 'theUser');
        $reflection->setAccessible(true);

        $shouldSkip = $job->shouldSkipProperty($reflection);

        $this->assertTrue($shouldSkip);
    }
}
