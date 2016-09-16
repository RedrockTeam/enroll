<?php

namespace App\Modules\Enroll\Http\Controllers;

use App\Http\Requests;
use App\Modules\Enroll\Models\Sinnjinn;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\DB;

class ScoreController extends Controller
{
    /**
     * 渲染笔试成绩登记页面, 只查询报了红岩三大技术部门的信息
     */
    public function render()
    {
        $data = DB::table('sinnjinn')
                ->select('sinnjinn.user_id', 'full_name', 'student_code', 'contact', 'score')
                ->join('apply_data_ex', 'apply_data_ex.user_id', '=', 'sinnjinn.user_id')
                ->where('current_step', '>=', 1)
                ->whereIn('apply_data_ex.dept_name', ['红岩网校工作站|web研发部', '红岩网校工作站|移动开发部', '红岩网校工作站|运维安全部'])
                ->get();

        return view('enroll::score')->with('data', $data);
    }

    /**
     * 保存用户分数
     */
    public function write(Request $request)
    {
        if (!$request->has('id') || !$request->has('score'))
            return response()->json(['status' => -1, 'content' => '信息不完整, 请求重试。']);

        $user = Sinnjinn::find($request->input('id')); /** @var Sinnjinn $user */

        $score = $request->input('score');

        if (!is_numeric($score) || $score < 0 || $score > 100)
            return response()->json(['status' => -1, 'content' => '分数填写不正确']);

        if (!is_null($user)) {
            $user->setAttribute('score', $request->input('score'));
            if ($user->save())
                return response()->json(['status' => 0, 'content' => '分数保存成功']);
        }

        return response()->json(['status' => -2, 'content' => '服务器遇到了错误, 分数保存失败。']);
    }
}
