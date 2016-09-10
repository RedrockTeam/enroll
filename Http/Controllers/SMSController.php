<?php

namespace App\Modules\Enroll\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Log;
use App\Jobs\Enroll\SendCompleteSMS;

use App\Modules\Enroll\Models\SmsLog;
use App\Modules\Enroll\Models\Sinnjinn;
use App\Modules\Enroll\Models\DepartmentLog;
use App\Modules\Enroll\Models\CircuitDesigns;

class SMSController extends Controller
{
    /**
     * 短信发送服务密钥
     *
     * @var string
     */
    protected static $apiKey = 'api:key-b761c24f77fc5d77769d5a442ccacc10';

    protected static $apiURL = 'http://sms-api.luosimao.com/v1/send.json';

    public function send(Request $request)
    {
        // 需要发送短信的学生名单
        $data = $request->json()->all();
        $current = $request->session()->get('user_info.dept_id');
        $username = call_user_func(function (Request $request) {
            preg_match('/=([a-zA-Z0-9]+)\//', $request->cookie('enroll_master_credential'), $username);
            return array_last($username);
        }, $request);
        $deptName = implode('|', array_only($request->session()->get('user_info'), ['org_name', 'dept_name']));

        // 部门日志里统计剩余短信条数
        $deptLog = (new DepartmentLog())->getDepartmentLog($current, ['dept_log_id', 'total_flown_sms', 'current_flow','sms_template']);
        $dataCount = count($data);

        if (is_null($deptLog))
            return response()->json(['status' => -20, 'content' => '找不到对应的部门日志']);
        if ($deptLog->getAttributeValue('total_flown_sms') < $dataCount)
            return response()->json(['status' => -21, 'content' => '短信剩余条数不够, 请联系充值']);

        // 预先记录发送日志
        (new SmsLog())->setSendLog($current, $data, $username, $dataCount);

        // 获取下一环节
        $flow = (new CircuitDesigns())->getDepartmentCurrentCircuit($current, ['en_design_id', 'flow_structure', 'total_step']);

        if (is_null($flow))
            return response()->json(['status' => -20, 'content' => '找不到对应的招新流程']);
        if ($flow->getAttributeValue('total_step') <= ($step = $request->session()->get('current_flow.step')))
            return response()->json(['status' => 0, 'content' => '已经处于最终流程!']);

        $nextFlow = unserialize($flow->getAttributeValue('flow_structure'))[$step + 1];

        // 依次发送到任务队列里
        foreach ($data as $index => $item) {
            $student = Sinnjinn::where(['full_name' => $item['name'], 'student_code' => $item['code']])->first();

            if (is_null($student)) {
                Log::error('##Enroll: 姓名为' . $item['name'] . ', 学号为' . $item['code'] . '的这位同学的信息不存在于数据库中');
            }

            $this->dispatch((new SendCompleteSMS($student, $deptLog, $nextFlow, $deptName))->onQueue('sms'));
        }

        return response()->json(['status' => 0, 'content' => '后台开始执行短信发送服务!']);
    }
}
