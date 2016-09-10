<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDepartmentLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('department_log', function (Blueprint $table) {
            $table->increments('dept_log_id');
            // 需要记录的信息
            $table->unsignedInteger('total_flown_sms');
            $table->string('current_flow');
            $table->tinyInteger('can_enroll');
            $table->string('sms_template'. 100);
            // 记录时留下的证据
            $table->integer('which_having');
            $table->string('who_write');
            $table->date('in_year');

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
        Schema::drop('department_log');
    }
}