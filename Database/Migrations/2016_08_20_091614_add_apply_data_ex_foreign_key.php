<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddApplyDataExForeignKey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('apply_data_ex', function (Blueprint $table) {
            // 关联用户到申请信息中
            $table->foreign('user_id')->references('id')->on(env('ZEUS_DATABASE') . '.student_information');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('apply_data_ex', function (Blueprint $table) {
            $table->dropForeign('user_id');
        });
    }
}