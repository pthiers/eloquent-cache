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
    return [
        'name' => $faker->word,
    ];
});

$factory->define(Post::class, function (Faker $faker) {
    return [
        'body' => $faker->word,
    ];
});

$factory->define(Video::class, function (Faker $faker) {
    return [
        'body' => $faker->word,
    ];
});