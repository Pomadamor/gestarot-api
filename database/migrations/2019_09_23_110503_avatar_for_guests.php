<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AvatarForGuests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('games_users', function (Blueprint $table) {
            $table->string('image')
                ->nullable();
            $table->string('avatar')
                ->nullable();
            $table->string('color')
                ->nullable();
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
            $table->dropColumn('color');
            $table->dropColumn('avatar');
            $table->dropColumn('image');
        });
    }
}
