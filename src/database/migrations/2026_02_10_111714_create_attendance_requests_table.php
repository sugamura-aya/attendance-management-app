<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();// 外部キー（usersテーブルのIDを参照）
            $table->foreignId('attendance_id')->nullable()->constrained();// 外部キー（attendance_requestsテーブルのIDを参照）(->nullable() を足して空っぽでもOKにする→勤怠記録がないの過去分を詳細画面で修正実行した際に有効にするためNULLに)
            $table->date('date');
            $table->time('clock_in');
            $table->time('clock_out');
            $table->text('remarks'); 
            
            // 仕様書のTINYINTに合わせて tinyInteger を使う
            $table->tinyInteger('status')->default(0)->comment('0:承認待ち, 1:承認済み');
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
        Schema::dropIfExists('attendance_requests');
    }
}
