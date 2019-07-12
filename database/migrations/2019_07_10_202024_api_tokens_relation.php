<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ApiTokensRelation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('value', 80);
            $table->dateTime('expires');

            $table->foreign('user_id')->references('id')->on('users')
                ->onDelete('cascade');
        });

        // Remove column api_token from user table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('api_token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('api_tokens');
        Schema::table('users', function (Blueprint $table) {
            $table->string('api_token', 80)->after('password')
                ->unique()
                ->nullable()
                ->default(null);
        });
    }
}
