<?php

namespace UnionWorx\LaravelSerializesModelsWithCache\Tests\Resources;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $primaryKey = 'id';

    protected $fillable = ['id', 'name'];
}
