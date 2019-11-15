<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_shops', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('shop_name', 100);
            $table->string('shop_address', 255);
            $table->string('phone', 20);
            $table->string('shop_summary', 255);
            $table->string('shop_picture', 100);
            $table->string('shop_icon', 100);
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
        Schema::dropIfExists('user_shops');
    }
}
