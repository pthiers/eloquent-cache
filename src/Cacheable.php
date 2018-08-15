<?php

namespace Authentik\EloquentCache;

use Illuminate\Support\Facades\Cache;

trait Cacheable {
    public function getCacheTTL() {
        return 0;
    }


    public function getCacheTagName() {
        return strtolower((new \ReflectionClass($this))->getShortName());
    }


    public function isCacheBustingEnabled() {
        return true;
    }


    public function isStaticCacheEnabled() {
        return true;
    }


    public static function boot() {
        static::saved(function ($model) {
            if ($model->isCacheBustingEnabled()) {
                self::flush($model);
            }
        });

        static::deleted(function ($model) {
            if ($model->isCacheBustingEnabled()) {
                self::flush($model);
            }
        });

        parent::boot();
    }


    /**
     * Create a new Cache query builder for the model.
     */
    public function newEloquentBuilder($query)
    {
        return new CacheQueryBuilder($query, $this);
    }


    public function cache() {
        $keyName = $this->getKeyName();
        $keyValue = $this->{$keyName};

        if ($this->getCacheTTL() > 0) {

            Cache::tags($this->getCacheTagName())
                ->remember($keyValue, $this->getCacheTTL(), function () {
                    return $this->toArray();
                });

        } else {

            Cache::tags($this->getCacheTagName())
                ->rememberForever($keyValue, function () {
                    return $this->toArray();
                });
        }
    }


    public function refresh() {
        self::flush($this);

        return parent::refresh();
    }


    public static function flush($model = null) {
        if (is_null($model)) {
            $model = new static;
            $tagName = $model->getCacheTagName();

            Cache::tags($tagName)->flush();

            if ($model->isStaticCacheEnabled()) {
                CacheQueryBuilder::$staticCache[$tagName] = [];
            }

        } else {
            $tagName = $model->getCacheTagName();

            $keyName = $model->getKeyName();
            $keyValue = $model->{$keyName};

            Cache::tags($tagName)->forget($keyValue);

            if ($model->isStaticCacheEnabled()) {
                if (isset(CacheQueryBuilder::$staticCache[$tagName][$keyValue])) {
                    unset(CacheQueryBuilder::$staticCache[$tagName][$keyValue]);
                }
            }
        }
    }
}