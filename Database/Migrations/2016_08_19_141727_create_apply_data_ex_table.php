<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApplyDataExTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('apply_data_ex', function (Blueprint $table) {
            $table->increments('enroll_id');
            // 保证外键类型相同
            $table->integer('user_id');
            $table->string('dept_name', 18);
            // 与用户相关的状态信息
            $table->tinyInteger('current_step');
            $table->boolean('was_send_sms');
            // 表明该用户什么时候报名
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('apply_data_ex');
    }
}