<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->string('state');
            $table->json('channels');
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
