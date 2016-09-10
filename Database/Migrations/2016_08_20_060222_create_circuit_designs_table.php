<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCircuitDesignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('circuit_designs', function (Blueprint $table) {
            $table->increments('en_design_id');

            // 创建该流程的时候
            // 指明使用该服务的对象
            $table->integer('for_dept_id');
            // 定义整个部门报名流程
            $table->tinyInteger('total_step');
            $table->text('flow_structure');

            $table->string('created_user', 45);
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
        Schema::drop('circuit_designs');
    }
}