<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOyabunTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('oyabun', function (Blueprint $table) {
            $table->increments('user_id');
            // 登录需要使用的信息
            $table->string('username', 45);
            $table->string('password', 99);
            $table->rememberToken();
            // 与该帐号关联的ID
            $table->integer('ref_id');
            // 是否拥有编辑其他部门的权限
            $table->boolean('out_of_dept');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('oyabun');
    }
}