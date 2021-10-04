<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZoomMeetingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('zoom_meeting', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->bigInteger('schedule_timestamp');
            $table->string('zoom_meeting_id')->nullable(true);
            $table->string('operation_type');
            $table->boolean('status');
            $table->json('response_data')->nullable();
            $table->timestamps();
        });


        Schema::table('zoom_meeting', function (Blueprint $table) {

            $table->foreign('conversation_id')->references('id')->on('conversations');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('zoom_meeting');
    }
}
