<?php
namespace Tests\Models;

use Authentik\EloquentCache\Cacheable;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model {
    protected $table = 'comments';

    use Cacheable;

    public function isStaticCacheEnabled() {
        return false;
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function commentable()
    {
      return $this->morphTo();
    }
    
}