<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVideo extends Migration
{
    public function up()
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('url');
            $table->unsignedInteger('user_id');
            $table->timestamps();
        });
    }

    public function down()
    {
    }
}
