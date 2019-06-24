<?php

use Faker\Generator as Faker;
use Tests\Models\{
	Category,
	CustomCategory,
	Product,
    CategorySoftDelete,
    User,
    Comment,
    Post,
    Video
};

$factory->define(Category::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
    ];
});

$factory->define(CustomCategory::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
    ];
});

$factory->define(CategorySoftDelete::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
    ];
});

$factory->define(Product::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
    ];
});

$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
    ];
});
$factory->define(Comment::class, function (Faker $faker) {

    $commentables = [
        Post::class,
        Video::class
    ];

    $commentableType = $faker->randomElement($commentables);
    $commentable = factory($commentableType)->create();

    return [
        'name' => $faker->word,
        'user_id' => factory(User::class)->create()->id,
        'commentable_id' => $commentable->id, 
        'commentable_type' => $commentableType
    ];
});

$factory->define(Post::class, function (Faker $faker) {
    return [
        'body' => $faker->word,
        'user_id' => factory(User::class)->create()->id,
    ];
});

$factory->define(Video::class, function (Faker $faker) {
    return [
        'url' => $faker->url,
        'user_id' => factory(User::class)->create()->id,
    ];
});