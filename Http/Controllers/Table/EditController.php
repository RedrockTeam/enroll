<?php

namespace App\Modules\Enroll\Http\Controllers\Table;

use App\Http\Requests;
use App\Jobs\Enroll\HandleVerifyAndRegister;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\DB;

use App\Modules\Enroll\Models\Sinnjinn;
use App\Modules\Enroll\Models\ApplyData;
use App\Modules\Enroll\Models\DepartmentLog;
use App\Modules\Enroll\Models\CircuitDesigns;

class EditController extends Controller
{
    /**
     * 临时存储用户报名信息
     *
     * @var array|static[]
     */
    protected static $apply = [];

    /**
     * 处理报名接口请求
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        // 报名状态标识
        $master = false;
        $second = false;

        if ($request->hasCookie('enroll_master_credential') /** 需要验证cookie */) {
            $master = true;

            if ($request->session()->has('is_admin'))
                return response()->json(['status' => -99, 'content' => '服务器一脸傲娇地拒绝了你的请求']);
        }

        // 验证用户
        if (!$master) {
            $verifyData = $this->verify($request->input('code'), $request->input('pass'));

            if ($verifyData['status'] != 200)
                return response()->json(['status' => -10, 'content' => '用户输入的学号和密码不相符']);
            if ($verifyData['data']['name'] != $request->input('name'))
                return response()->json(['status' => -10, 'content' => '对不起, 你的学号和姓名不符']);
        }

        if ($master) {
            $admin = $request->session()->get('user_info');
            // 表明该报名请求是从后台管理提交过来的
            self::$apply = [['organization' => $admin['org_name'], 'department' => $admin['dept_name']]];
        } else
            self::$apply = array_flatten($request->only('choice'), 1);

        $user = (new Sinnjinn())->getStudentByCode($request->only(['name', 'code']));

        // 第二次报名, 以新用户(即用户属性不为空)为标识
        if (!empty($user->getAttributes())) {
            $second = true;

            // 查找是否报名过填写的部门
            if ($user->withApply()->getBaseQuery()->whereIn('dept_name', array_map(function ($v) {
                return implode('|', $v);
            }, self::$apply))->exists())
                return response()->json(['status' => -3, 'content' => '用户报名过该部门!']);
        }

        if (!$second && !$user->setStudentInformation($request->except(['choice', 'pass', '_token'])))
            return response()->json(['status' => -40, 'content' => '用户报名失败, 请联系红岩网校。']);

        $this->dispatch((new HandleVerifyAndRegister(
            $user, self::$apply, $second
        ))->onQueue('handle')->delay(30));

        return response()->json(['status' => 0, 'content' => '正在处理用户的报名请求, 请稍后查询你的报名状态']);
    }

    /**
     * 进入下一报名流程
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkout(Request $request)
    {
        if ($request->session()->has('is_admin'))
            return response()->json(['status' => -99, 'content' => '服务器一脸傲娇地拒绝了你的请求']);

        $id = $request->session()->get('user_info.dept_id');

        // 判断是否设置过流程
        if (!$request->session()->has('current_flow.' . $id))
            return response(['status' => -2, 'content' => '当前部门还未开启招新!']);

        $oldCurrent = $request->session()->get('current_flow')[$id]['step'];
        $structures = (new CircuitDesigns())->getDepartmentCurrentCircuit($id, ['total_step', 'flow_structure']);

        // 避免随意更改流程编号
        if ($structures['total_step'] <= $oldCurrent + 1)
            return response()->json(['status' => 0, 'content' => '已经处于最终流程!']);

        $current = unserialize($structures['flow_structure'])[$oldCurrent + 1];

        if (Carbon::createFromFormat('Y-m-d', $current['time']) > Carbon::createFromTimestamp(time()))
            return response()->json(['status' => -13, 'content' => '下一流程的预定开启时间还未到, 无法切换到下一流程']);

        // 保存下一报名流程
        if ((new DepartmentLog())->setDepartmentCurrentStep($id, array_merge($current, ['step' => $oldCurrent + 1]))) {
            // 把所有未到当前流程的学生的状态设置为相反数
            DB::connection('apollo')->update(
                'UPDATE `apply_data_ex` SET `current_step` = 0 - `current_step`, `was_send_sms` = 0 WHERE `current_step` < ? AND `current_step` > 0',
                [$oldCurrent + 1]
            );
            // 并且重置发送短信的状态
            DB::connection('apollo')->update(
                'UPDATE `apply_data_ex` SET `was_send_sms` = 0 WHERE `current_step` = ?',
                [$oldCurrent + 1]
            );

            return response()->json(['status' => 0, 'content' => '成功切换至下一流程。']);
        }
    }

    /**
     * 提升报名用户的流程状态
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function update(Request $request)
    {
        if ($request->session()->has('is_admin'))
            return response()->json(['status' => -99, 'content' => '服务器一脸傲娇地拒绝了你的请求']);

        $id = $request->session()->get('user_info.dept_id');

        if (!$request->session()->has('current_flow.' . $id))
            return response(['status' => -2, 'content' => '当前部门还未开启招新!']);

        $thisStep = $request->session()->get('current_flow')[$id]['step'];
        $finalStep = (new CircuitDesigns())->getDepartmentCurrentCircuit(
            $request->session()->get('user_info.dept_id'), ['total_step']
        )->getAttribute('total_step');
        // 设置下一个流程
        $nextStep = $thisStep == $finalStep ? $finalStep : $thisStep + 1;

        if (empty(($bag = $request->input('id')))) return ;

        foreach ($bag as $id => $switch) {
            $data = ApplyData::find($id, ['enroll_id', 'current_step']); /** @var ApplyData $data */
            $step = $data->getAttributeValue('current_step');

            if ($thisStep >= $step) {
                $data->setAttribute('current_step', $nextStep); // TODO 有必要考虑加日志
                if (!$data->save())
                    return response()->json([
                        'status' => -11,
                        'content' => $data->withUser()->getAttributeValue('name') . '之后的同学还未成功通过, 请重试!'
                    ]);
            }
        }

        return response()->json(['status' => 0, 'content' => '以上同学已经成功通过本轮, 可以发送通知短信了!']);
    }

    /**
     * 获取当前部门的地点和短信模板
     */
    public function step(Request $request)
    {
        if ($request->session()->has('is_admin'))
            return response()->json(['status' => -99, 'content' => '服务器一脸傲娇地拒绝了你的请求']);

        $id = $request->session()->get('user_info.dept_id');

        if (!$request->session()->has('current_flow.' . $id))
            return response(['status' => -2, 'content' => '当前部门还未开启招新!']);

        $current = $request->session()->get('current_flow')[$id];

        // 如果带有可修改的Token就跳转到修改函数中
        if ($request->query->has('modify_token')
            && $request->session()->get('modify_token') == $request->query('modify_token')
        ) {
            preg_match('/=([a-zA-Z0-9]+)\//', $request->cookie('enroll_master_credential'), $username);

            // 删除Token
            $request->session()->remove('modify_token');

            return $this->modify($id, $request->only(['location', 'remark']), $current, array_last($username));
        }

        $token = uniqid('mkKey/', true);
        // Session中存取Token
        $request->session()->set('modify_token', $token);

        return response()->json([
            'status' => 0,
            'content' => '成功读取当前部门的地点和短信模板',
            'extra' => array_only($current, ['location', 'remark']),
            'token' => $token
        ]);
    }

    /**
     * 更改当前部门的地点和短信模板
     */
    protected function modify($id, array $new, array $old, $author)
    {
        if (empty($new['location']) || empty($new['remark']))
            return ;

        // 替换地点和短信模板
        $new = array_replace($old, $new);

        if ((new DepartmentLog())->setDepartmentCurrentFlow($id, $new, $author))
            return response()->json(['status' => 0, 'content' => '保存地点和短信模板成功']);
    }

    /**
     * 验证用户学号和身份证后六位
     *
     * @param string $code
     * @param string $pass
     *
     * @return mixed
     */
    protected function verify($code, $pass)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://hongyan.cqupt.edu.cn/api/verify');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt ($ch, CURLOPT_POSTFIELDS, http_build_query(['stuNum' => $code, 'idNum' => $pass]));

        $output = curl_exec($ch);
        curl_close ( $ch );

        return json_decode($output, true);
    }
}
