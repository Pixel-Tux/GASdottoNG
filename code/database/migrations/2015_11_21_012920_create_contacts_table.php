<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateContactsTable extends Migration
{
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->timestamps();

            $table->string('target_type');
            $table->string('target_id');
            $table->string('type');
            $table->string('value');
        });
    }

    public function down()
    {
        Schema::drop('contacts');
    }
}
