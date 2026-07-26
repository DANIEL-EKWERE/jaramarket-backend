<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFranchisesTable extends Migration
{
    public function up()
    {
        Schema::create('franchises', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location');
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('franchises');
    }
}
