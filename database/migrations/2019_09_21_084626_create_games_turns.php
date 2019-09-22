<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGamesTurns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('games_turns', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->unsignedBigInteger('game_id');
            $table->foreign('game_id')->references('id')->on('games')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            
            $table->string('preneur');
            $table->string('partenaire')
                ->nullable();
            
            $table->enum('roi', ['trefle', 'carreau', 'coeur', 'pique'])
                ->nullable();
            $table->enum('type', ['petite', 'garde', 'garde sans', 'garde contre']);

            $table->integer('score')->default(0);
            $table->integer('autre_score')->default(0);

            $table->integer('nbJoueur')->default(0);
            $table->integer('scoreJ1')->default(0);
            $table->integer('scoreJ2')->default(0);
            $table->integer('scoreJ3')->default(0);
            $table->integer('scoreJ4')->default(0);
            $table->integer('scoreJ5')->default(0);

            $table->boolean('victoire')->default(FALSE);
        });

        Schema::table('games_users', function (Blueprint $table) {
            $table->unsignedInteger('position')
                ->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('games_users', function (Blueprint $table) {
            $table->dropColumn('position');
        });
        Schema::dropIfExists('games_turns');
    }
}
