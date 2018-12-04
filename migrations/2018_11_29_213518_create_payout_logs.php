<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePayoutLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payout_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('item_id', 100);
            $table->string('transaction_id', 100)->nullable();
            $table->string('email', 50);
            $table->float('amount');
            $table->string('status', 20)->default('PENDING');
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
        Schema::dropIfExists('payout_logs');
    }
}
