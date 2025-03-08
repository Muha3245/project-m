<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('calls', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('caller_id');
        $table->unsignedBigInteger('group_id');
        $table->unsignedBigInteger('receiver_id');
        $table->unsignedBigInteger('sender_id');
        $table->enum('call_type', ['audio', 'video']);
        $table->enum('status', ['calling', 'answered', 'ended'])->default('calling');
        $table->timestamps();

        $table->foreign('caller_id')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('receiver_id')->references('id')->on('users')->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
