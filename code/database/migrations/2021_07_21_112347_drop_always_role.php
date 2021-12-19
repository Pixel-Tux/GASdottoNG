<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class DropAlwaysRole extends Migration
{
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('always');
        });
    }

    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('always')->default(false);
        });
    }
}
