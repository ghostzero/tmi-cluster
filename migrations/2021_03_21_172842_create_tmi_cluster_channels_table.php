<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTmiClusterChannelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tmi_cluster_channels', function (Blueprint $table) {
            $table->string('id', 25)->primary();
            $table->boolean('revoked')->default(false);
            $table->boolean('reconnect')->default(false);
            $table->uuid('supervisor_process_id')->nullable();
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
        Schema::dropIfExists('tmi_cluster_channels');
    }
}
