<?php
namespace Tests\Models;

use Authentik\EloquentCache\Cacheable;
use Illuminate\Database\Eloquent\Model;

class User extends Model {
    protected $table = 'users';

    use Cacheable;

    public function isStaticCacheEnabled() {
        return false;
    }

    public function comments(){
        return $this->hasMany(Comment::class);
    }
}