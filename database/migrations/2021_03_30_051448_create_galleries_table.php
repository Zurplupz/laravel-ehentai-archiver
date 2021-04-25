<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGalleriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('galleries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->integer('gid');
            $table->string('token', 50);
            $table->smallInteger('credits')->nullable(true);
            $table->smallInteger('gp')->nullable(true);
            $table->timestamp('favorited')->nullable(true);
            $table->tinyInteger('archived')->default('0');
            $table->string('archive_path')->nullable(true);
            $table->string('archiver_key', 100)->nullable(true);
            $table->smallInteger('credits')->default('0');
            $table->string('category', 50)->nullable(true);
            $table->string('thumb')->nullable(true);
            $table->string('uploader')->nullable(true);
            $table->integer('posted')->nullable(true);
            $table->smallInteger('filecount')->nullable(true);
            $table->integer('filesize')->nullable(true);
            $table->tinyInteger('expunged')->default('0')->nullable(true);
            $table->string('rating')->nullable(true);
            $table->smallInteger('torrentcount')->nullable(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('galleries');
    }
}
