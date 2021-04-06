<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddTmiClusterTablePrefixes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('supervisors', 'tmi_cluster_supervisors');
        Schema::rename('supervisor_processes', 'tmi_cluster_supervisor_processes');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('tmi_cluster_supervisors', 'supervisors');
        Schema::rename('tmi_cluster_supervisor_processes', 'supervisor_processes');
    }
}
