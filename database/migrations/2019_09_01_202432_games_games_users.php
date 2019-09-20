<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class GamesGamesUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('games', function (Blueprint $table) {
            $table->bigIncrements('id');
            // $table->enum('type', ['petite', 'garde', 'garde sans', 'garde contre']);
            $table->enum('status', ['started', 'in progress', 'ended'])
                ->default('started');
        });

        Schema::create('games_users', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('game_id');

            $table->unsignedBigInteger('user_id')
                ->nullable();
            
            $table->string('username', 100)
                ->nullable();

            $table->enum('type', ['user', 'guest'])
                ->default('user');

            $table->boolean('is_owner')
                ->default(FALSE);

            $table->foreign('user_id')->references('id')->on('users')
                ->onDelete('cascade');

            $table->foreign('game_id')->references('id')->on('games')
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
        Schema::dropIfExists('games');
        Schema::dropIfExists('games_users');
    }
}
