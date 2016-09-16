<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSinnjinnTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sinnjinn', function (Blueprint $table) {
            $table->increments('user_id');
            // 报名时填写的信息
            $table->string('full_name', 22);
            $table->enum('gender', ['男', '女']);
            $table->string('student_code', 10);
            $table->enum('college', ['通信与信息工程学院', '光电工程学院', '经济管理学院', '计算机科学与技术学院', '外国语学院', '生物信息学院', '法学院', '自动化学院', '体育学院', '理学院', '传媒艺术学院', '软件工程学院', '先进制造工程学院', '国际学院', '国际半导体学院']);
            $table->string('contact', 11);
            $table->unsignedTinyInteger('having_org');
            /** TODO 增加分数列 */
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sinnjinn');
    }
}