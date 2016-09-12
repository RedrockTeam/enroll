<?php

namespace App\Modules\Enroll\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Enroll\Models\DepartmentLog;
use App\Modules\Enroll\Models\CircuitDesigns;

class SetupController extends Controller
{
    /**
     * 渲染流程环节设计页面
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $circuit = (new CircuitDesigns())->getDepartmentCurrentCircuit(
            $request->session()->get('user_info.dept_id')
        );

        $redirect = redirect()->to('/enroll/dashboard');
        $redirect->setSession($request->session());

        if (!is_null($circuit)) return $redirect;

        return view('enroll::pages.setup', ['title' => '描述你的部门招新流程环节']);
    }

    /**
     * 为某个部门创建新的招新流程
     *
     * @param Request $request
     */
    public function create(Request $request)
    {
        $k = 0;
        $data = [];
        $locate = [];
        // 拉取输入数据
        $input = $request->json()->all();
        $inputLength = count($input);

        for ($i = 0; $i < $inputLength; $i += 4) {
            foreach (['type', 'time', 'location', 'remark'] as $value) {
                if ($value == 'location')
                    array_push($locate, array_shift($input)['value']);
                else
                    $data[$k][$value] = array_shift($input)['value'];
            }

            // 统计流程数量
            $k++;
        }

        // 先弹出报名流程的空地址
        array_shift($locate); $i = 0;
        // 再依次加入到上一个流程的地址中
        while ($k--)
            $data[$i++]['location'] = array_shift($locate);

        $design = new CircuitDesigns();
        // 保存环节流程为JSON字符串
        $design->setAttribute('flow_structure', serialize($data));
        $design->setAttribute('total_step', $i);
        // 记录日志
        $design->setAttribute('for_dept_id', $request->session()->get('user_info.dept_id'));

        if (!$design->save())
            return response()->json(['status' => -10, 'content' => '无法保存招新流程']);

        preg_match('/=([a-zA-Z0-9]+)\//', $request->cookie('enroll_master_credential'), $username);
        // 初始化报名流程
        (new DepartmentLog())->setDepartmentLog(
            $design->getAttributeValue('for_dept_id'), array_merge(array_first($data), ['step' => 0]), 1000, array_last($username)
        );
    }
}
