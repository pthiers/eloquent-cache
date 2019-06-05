<?php
namespace Tests\Models;

use Authentik\EloquentCache\Cacheable;
use Illuminate\Database\Eloquent\Model;

class Post extends Model {
    protected $table = 'posts';

    use Cacheable;

    public function isStaticCacheEnabled() {
        return false;
    }

    public function comments()
    {
      return $this->morphMany(Comment::class, 'commentable');
    }
}