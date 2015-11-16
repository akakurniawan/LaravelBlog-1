<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('title');
            $table->string('slug')->unique('slug');
            $table->string('thumb')->nullable();
            $table->text('excerpt');
            $table->text('body');
            $table->tinyInteger('is_active')->unsigned()->default(0);
            $table->tinyInteger('comment_status')->unsigned()->default(0);
            $table->integer('comment_count')->unsigned()->default(0);
            $table->timestamps();

            // 生成外键，并且指定在删除用户时同时删除该用户的所有文章
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('articles');
    }
}