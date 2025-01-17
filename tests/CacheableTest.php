<?php
namespace Tests;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Cache;
use Tests\Models\{
    Category,
    CustomCategory,
    Product,
    CategorySoftDelete,
    User,
    Video,
    Post,
    Comment
};
use Authentik\EloquentCache\CacheQueryBuilder;
use Orchestra\Database\ConsoleServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CacheableTest extends TestCase
{
    protected function setUp(): void {
        parent::setUp();

        $this->withFactories(__DIR__ . '/database/factories');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        factory(Category::class, 20)->create();

        factory(Comment::class, 20)->create();

        $GLOBALS['cache_busting'] = true;
    }

    protected function getPackageProviders($app)
    {
        return [
            ConsoleServiceProvider::class,
        ];
    }
    

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    protected function getCachedInstance(Model $model, $id, $nullConditions = null) {
        $builder = $model->newQueryWithoutScopes();

        if ($nullConditions) {
            $tmp = $nullConditions;
            $nullConditions = new Collection(null);

            foreach ($tmp as $k => $v) {
                $nullConditions->push([
                    'column' => $k,
                    'type' => $v,
                ]);
            }
        }

        return $this->invokeMethod($builder, 'getCachedInstance', [$id, $nullConditions]);
    }

    public function testCache() {
        $instance = $model = Category::first();
        $builder = $instance->newQueryWithoutScopes();

        $this->assertInstanceOf(\Authentik\EloquentCache\CacheQueryBuilder::class, $builder);
        $this->assertNotNull($this->getCachedInstance($model, $instance->id));
        $this->assertArrayNotHasKey('category', CacheQueryBuilder::$staticCache);

        $cachedInstance = $this->getCachedInstance($model, $instance->id);
        $this->assertTrue($cachedInstance->is($instance));
        $this->assertEquals($instance->toArray(), $cachedInstance->toArray());

        $this->assertTrue($cachedInstance->exists);

        // Just for code coverage purposes
        Category::find([1, 2]);
    }


    public function testFlush() {
        $ids = [1, 5];

        $instances = Category::find($ids);
        $model = $instances->first();

        foreach ($ids as $id) {
            $this->assertNotNull($this->getCachedInstance($model, $id));
        }

        Category::flush();

        foreach ($ids as $id) {
            $this->assertNull($this->getCachedInstance($model, $id));
        }
    }


    public function testCacheBusting() {
        $instances = [
            Category::inRandomOrder()->first(),
            CustomCategory::inRandomOrder()->first()
        ];

        foreach ($instances as $instance) {
            $model = $instance;

            $instance->cache();
            $this->assertNotNull($this->getCachedInstance($model, $instance->id));


            $instance->name .= '-suffix';
            $instance->save();
            $this->assertNull($this->getCachedInstance($model, $instance->id));


            $instance->cache();
            $this->assertNotNull($this->getCachedInstance($model, $instance->id));


            $instance->delete();
            $this->assertNull($this->getCachedInstance($model, $instance->id));
        }
    }


    public function testCustomTagNameAndTTL() {
        $ids = [3, 6];

        $instances = CustomCategory::whereIn('id', $ids)->get();
        $model = $instances->first();

        $this->assertArrayHasKey('custom_category', CacheQueryBuilder::$staticCache);

        foreach ($ids as $id) {
            $this->assertNotNull($this->getCachedInstance($model, $id));
            $this->assertArrayHasKey($id, CacheQueryBuilder::$staticCache['custom_category']);
        }

        CustomCategory::flush($instances->where('id', $ids[0])->first());

        $this->assertNull($this->getCachedInstance($model, $ids[0]));   
        $this->assertArrayNotHasKey($ids[0], CacheQueryBuilder::$staticCache['custom_category']);

        $this->assertNotNull($this->getCachedInstance($model, $ids[1]));   
        $this->assertArrayHasKey($ids[1], CacheQueryBuilder::$staticCache['custom_category']);



        CustomCategory::flush();

        foreach ($ids as $id) {
            $this->assertNull($this->getCachedInstance($model, $id));   
            $this->assertArrayNotHasKey($id, CacheQueryBuilder::$staticCache['custom_category']);
        }
    }


    public function testNoCacheBusting() {
        $GLOBALS['cache_busting'] = false;

        $instance = $model = CustomCategory::inRandomOrder()->first();

        $instance->cache();
        $this->assertNotNull($this->getCachedInstance($model, $instance->id));


        $instance->name .= '-suffix';
        $instance->save();
        $this->assertNotNull($this->getCachedInstance($model, $instance->id));


        CustomCategory::flush($instance);
        $this->assertNull($this->getCachedInstance($model, $instance->id));

        $instance->cache();
        $this->assertNotNull($this->getCachedInstance($model, $instance->id));

        $instance->delete();
        $this->assertNotNull($this->getCachedInstance($model, $instance->id));
    }

    public function testNonExistingInstances() {
        $instance = $model = Category::find(2)->refresh();
        $this->assertNotNull($this->getCachedInstance($model, 2));


        Category::find(2);
        $this->assertNotNull($this->getCachedInstance($model, 2));

        $this->assertNull(Category::find(99));
        $this->assertNull($this->getCachedInstance($model, 99));

        Category::flush();

        $instances = Category::find([2, 0, 99]);
        $this->assertEquals(1, $instances->count());
        $this->assertEquals(2, $instances->first()->id);

        $this->assertNotNull($this->getCachedInstance($model, 2));
        $this->assertNull($this->getCachedInstance($model, 0));
        $this->assertNull($this->getCachedInstance($model, 99));
    }

    public function testRelation() {
        $instance = $model = Category::first();

        $instance->parent_id = 20;

        $relations = ['parent', 'hasOneParent'];
        foreach ($relations as $relation) {
            $instance = $model->{$relation};

            $this->assertNotNull($this->getCachedInstance($model, $instance->id));

            $cachedInstance = $this->getCachedInstance($model, $instance->id);

            $this->assertInstanceOf(Category::class, $instance);
            $this->assertInstanceOf(Category::class, $cachedInstance);

            $this->assertEquals(20, $instance->id);
            $this->assertEquals(20, $cachedInstance->id);

            Category::flush();
        }

    }

    public function testEagerLoading() {
        $parentId = 20;

        $instance = $model = Category::find(1);

        $this->assertNotNull($this->getCachedInstance($model, $instance->id));

        $instance->parent_id = $parentId;
        $instance->save();

        $this->assertNotEquals($instance->parent_id, $instance->id);

        $this->assertNull($this->getCachedInstance($model, $instance->id));

        $instance = Category::with('parent')->find(1);

        $this->assertNotNull($this->getCachedInstance($model, $instance->id));
        $this->assertNotNull($this->getCachedInstance($model, $parentId));

        $cachedInstance = $this->getCachedInstance($model, $instance->id);

        $this->assertTrue($cachedInstance->is($instance));
        $this->assertInstanceOf(Category::class, $cachedInstance->parent);
    }

    public function testEagerPolymorphicLoading() {
        $video = $videoModel = Video::with('comments')->first();
        $post = $postModel = Post::with('comments.user')->first();

        $this->assertNotNull($this->getCachedInstance($videoModel, $video->id));
        $this->assertNotNull($this->getCachedInstance($postModel, $video->id));

        $videoWithUser = Video::with('comments.user')->first();

        $this->assertNotNull($this->getCachedInstance($videoWithUser, $video->id));
        $this->assertTrue($this->modelHasRelations($videoWithUser, 'comments'));
        $this->assertTrue($this->modelHasRelations($videoWithUser, 'comments.user'));

        $user_id = $videoWithUser->comments->first()->user_id;
        $dbUser = User::findOrFail($user_id);

        $this->assertNotNull($this->getCachedInstance($videoWithUser->comments->first()->user, $user_id));

    }

    public function testSoftDelete() {
        factory(CategorySoftDelete::class, 2)->create();

        $model = CategorySoftDelete::find(1);
        $this->assertNotNull($this->getCachedInstance($model, 1));
        $this->assertNotNull($this->getCachedInstance($model, 1, ['deleted_at' => 'Null']));
        $this->assertNull($this->getCachedInstance($model, 1, ['deleted_at' => 'NotNull']));

        CategorySoftDelete::find(2)->delete();
        $this->assertNull($this->getCachedInstance($model, 2));

        $model = CategorySoftDelete::withTrashed()
            ->whereNotNull('deleted_at')
            ->find(2);

        $this->assertNotNull($this->getCachedInstance($model, 2));
        $this->assertNotNull($this->getCachedInstance($model, 2, ['deleted_at' => 'NotNull']));
        $this->assertNull($this->getCachedInstance($model, 2, ['deleted_at' => 'Null']));
    }

    public function testComplicatedQueries() {
        $model = Category::first();

        $this->assertNotNull($this->getCachedInstance($model, 1));

        Category::flush();


        Category::where('id', '<', 3)->get();

        $this->assertNotNull($this->getCachedInstance($model, 1));
        $this->assertNotNull($this->getCachedInstance($model, 2));
        $this->assertNull($this->getCachedInstance($model, 3));

        Category::flush();


        factory(Product::class)->make(['category_id' => 10])->save();
        factory(Product::class)->make(['category_id' => 20])->save();

        Category::addSelect([
            'category.*'
        ])
        ->join('product', 'product.category_id', 'category.id')
        ->get();

        $this->assertNotNull($this->getCachedInstance($model, 10));
        $this->assertNotNull($this->getCachedInstance($model, 20));

        Category::flush();


        Category::addSelect([
            'category.id',
            'product.*',
        ])
        ->join('product', 'product.category_id', 'category.id')
        ->get();

        $this->assertNull($this->getCachedInstance($model, 10));
        $this->assertNull($this->getCachedInstance($model, 20));
    }

    public function testModelAppends()
    {
        $models = CustomCategory::get();

        // Retrieve from cache
        $model = CustomCategory::find($models->first()->id);

        // save() will crash `General error: 1 no such column: is_admin` if the $appends attributes
        // are in the model's attributes
        $model->save();
        $this->assertTrue(true);
    }

    protected function modelHasRelations(\Illuminate\Database\Eloquent\Model $model, $relations): bool {

        if (is_string($relations)) {
            $relations = explode('.', $relations);
        }

        $queue = array_reverse($relations);

        while (count($queue)) {

            $relation = $queue[count($queue) - 1];

            if ($model instanceof \Illuminate\Support\Collection) {

                if (
                    $model->contains(function ($item) use ($relation) {
                        return !$item->relationLoaded($relation);
                    })
                ) {
                    return false;
                }

                $model = $model->first();

            } else

            if (!$model->relationLoaded($relation)) {
                return false;
            }

            $model = $model[$relation];
            array_pop($queue);
        }

        return true;
    }
}