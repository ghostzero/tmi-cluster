<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupervisorProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supervisor_processes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('supervisor_id')->nullable();
            $table->string('state');
            $table->json('channels');
            $table->json('metrics')->nullable();
            $table->timestamp('last_ping_at');
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
        Schema::dropIfExists('supervisor_processes');
    }
}
