<?php

namespace App\Modules\Enroll\Http\Controllers\Table;

use App\Http\Requests;
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
    protected static $apply = ['user' => null, 'choice' => ''];

    /**
     * 处理报名接口请求
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        $master = false;
        $second = false;

        if ($request->hasCookie('enroll_master_credential') /** 需要验证cookie */) {
            $master = true;

            if ($request->session()->get('user_info.dept_id') != $request->session()->get('current_dept'))
                return response()->json(['status' => -99, 'content' => '服务器一脸傲娇地拒绝了你的请求']);
        }

        // 验证用户
        if (!$master && $this->verify($request->input('code'), $request->input('pass'))['status'] != 200)
            return response()->json(['status' => -10, 'content' => '用户输入的学号和密码不相符']);

        if ($master) {
            $admin = $request->session()->get('user_info');
            // 表明该报名请求是从后台管理提交过来的
            self::$apply['choice'] = [['organization' => $admin['org_name'], 'department' => $admin['dept_name']]];
        } else
            self::$apply['choice'] = array_flatten($request->only('choice'), 1);

        $tmp =  ['org' => [], 'dept' => []];
        $user = (new Sinnjinn())->getStudentByCode($request->only(['name', 'code']));
        $count = $user->getHavingOrganization();

        // 第二次报名, 以新用户(即用户属性不为空)为标识
        if (!empty($user->getAttributes())) {
            $second = true;

            // 查找是否报名过填写的部门
            if ($user->withApply()->getBaseQuery()->whereIn('dept_name', array_map(function ($v) {
                return implode('|', $v);
            }, self::$apply['choice']))->exists())
                return response()->json(['status' => -3, 'content' => '用户报名过该部门!']);
        }

        if (!$second && !$user->setStudentInformation($request->except(['choice', 'pass', '_token'])))
            return response()->json(['status' => -40, 'content' => '用户报名失败, 请联系红岩网校。']);

        self::$apply['user'] = $user;

        foreach (self::$apply['choice'] as $key => $choice) {
            if (!in_array($choice['organization'], $tmp['org'])) {
                // 临时保存组织名称, 用来检查是否报名超过三个组织
                array_push($tmp['org'], $choice['organization']);

                if ($count++ && $second) {
                    // 当二次报名时, 不能报更多部门, 特别是这些部门是属于其他组织的
                    if ($user->getStudentByOrganizationName($choice['organization'])->isEmpty()) {
                        if ($count >= 3)
                            return response()->json(['status' => -2, 'content' => '用户不能报名该部门, 因为用户不能再报名其他部门!']);
                    } else $count--;
                }
            }

            if ($count > 3)
                return response()->json([
                    'status' => -1, 'content' => '用户不能报名超过三个组织, 尚未报名的部门有: ' . implode(',', array_map(function ($v) {
                        return implode('-', $v);
                    }, self::$apply['choice']))]);

            $tmp['dept'][$choice['organization']] = [];

            if (!in_array($choice['department'], ($tmp_dept = &$tmp['dept'][$choice['organization']]))) {
                if (!$this->create(['user' => self::$apply['user'], 'dept' => $choice]))
                    return
                        response()->json([
                            'status' => -1, 'content' => '用户报名失败, 尚未报名的部门有: ' . implode(',', array_map(function ($v) {
                                return implode('-', $v);
                            }, self::$apply['choice']))
                        ]);

                array_push($tmp_dept, $choice['department']);
            }

            unset(self::$apply['choice'][$key]);
        }

        // 统计用户报名的组织数量
        $user->setAttribute('having_org', $count);
        $user->save();

        return response()->json(['status' => 0, 'content' => '用户报名成功!']);
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
        if ($request->session()->get('user_info.dept_id') != $request->session()->get('current_dept'))
            return response()->json(['status' => -99, 'content' => '服务器一脸傲娇地拒绝了你的请求']);

        $id = $request->session()->get('user_info.dept_id');
        $oldCurrent = $request->session()->get('current_flow.step');
        $structures = (new CircuitDesigns())->getDepartmentCurrentCircuit($id, ['total_step', 'flow_structure']);

        // 避免随意更改流程编号
        if ($structures['total_step'] <= $oldCurrent)
            return response()->json(['status' => 0, 'content' => '已经处于最终流程!']);

        $current = unserialize($structures['flow_structure'])[$oldCurrent + 1];

//        if (Carbon::createFromFormat('Y-m-d', $current['time'])->diffInDays() > 0)
//            return response()->json(['status' => -13, 'content' => '下一流程的预定开启时间还未到, 无法切换到下一流程']);

        // 保存下一报名流程
        if ((new DepartmentLog())->setDepartmentCurrentStep($id, array_merge($current, ['step' => $oldCurrent + 1]))) {
            // 把所有未到当前流程的学生的状态设置为相反数
            DB::connection('apollo')->update(
                'UPDATE `apply_data_ex` SET `current_step` = 0 - `current_step` WHERE `current_step` <= ? AND `current_step` > 0',
                [$oldCurrent + 1]
            );

            return response()->json(['status' => 0, 'content' => '成功切换至下一流程。']);
        }
    }

    /**
     * 保存用户的报名信息
     *
     * @param array $apply
     *
     * @return bool
     */
    protected function create(array $apply)
    {
        $ex = new ApplyData();

        // 关联报名用户
        $ex->withUser()->associate($apply['user']);
        // 保存报名信息
        $ex->setAttribute('current_step', 1);
        $ex->setAttribute('was_send_sms', 0);
        $ex->setAttribute('dept_name', implode('|', $apply['dept']));

        return $ex->save();
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
        if ($request->session()->get('user_info.dept_id') != $request->session()->get('current_dept'))
            return response()->json(['status' => -99, 'content' => '服务器一脸傲娇地拒绝了你的请求']);

        if (empty($bag = $request->input('id'))) return ;

        $current = $request->session()->get('current_flow')['step'];
        $finalStep = (new CircuitDesigns())->getDepartmentCurrentCircuit(
            $request->session()->get('user_info.dept_id'), ['total_step']
        )->getAttribute('total_step');
        // 设置下一个流程
        $nextStep = $current == $finalStep ? $finalStep : $current + 1;

        foreach ($bag as $id => $switch) {
            $data = ApplyData::find($id, ['enroll_id', 'current_step']); /** @var ApplyData $data */
            $step = $data->getAttributeValue('current_step');

            if ($current >= $step) {
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
