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
        $input = $request->json()->all();
        $inputLength = count($input);
        $design = new CircuitDesigns();

        for ($i = 0; $i < $inputLength; $i += 4) {
            foreach (['type', 'time', 'location', 'remark'] as $value)
                $data[$k][$value] = array_shift($input)['value'];

            // 统计流程数量
            $k++;
        }

        // 保存环节流程为JSON字符串
        $design->setAttribute('flow_structure', serialize($data));
        $design->setAttribute('total_step', $k);
        // 记录日志
        $design->setAttribute('for_dept_id', $request->session()->get('user_info.dept_id'));
        $design->saveOrFail();

        preg_match('/=([a-zA-Z0-9]+)\//', $request->cookie('enroll_master_credential'), $username);
        // 初始化报名流程
        (new DepartmentLog())->setDepartmentLog(
            $design->getAttributeValue('for_dept_id'), array_merge(array_first($data), ['step' => 1]), 1000, array_last($username)
        );
    }
}
