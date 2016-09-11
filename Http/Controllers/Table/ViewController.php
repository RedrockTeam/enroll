<?php

namespace App\Modules\Enroll\Http\Controllers\Table;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Modules\Enroll\Http\Requests\DataTableRequest;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Pagination\LengthAwarePaginator;

use App\Modules\Enroll\Models\Sinnjinn;
use App\Modules\Enroll\Models\ApplyData;
use App\Modules\Enroll\Models\DepartmentLog;
use App\Modules\Enroll\Models\CircuitDesigns;
use App\Modules\Pvdt\Models\DepartmentStructures;
use Illuminate\Support\Facades\Log;

class ViewController extends Controller
{
    /**
     * 表格数据规范
     *
     * @var static[]
     */
    protected static $records = [
        'checkboxes' => null,
        'name' => 'full_name', 'gender' => 'gender', 'code' => 'student_code',
        'college' => 'college', 'phone' => 'contact', 'intention' => 'intention',
        'status' => 'circuit_status'
    ];

    /**
     * 流程类型
     *
     * @var static[]
     */
    protected static $flown = ['报名', '第一轮', '第二轮', '第三轮', '第四轮'];

    /**
     * 存放临时资源
     *
     * @var static[]
     */
    protected static $data = [];

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        $this->middleware(\App\Modules\Enroll\Http\Middleware\ValidDataCollection::class, ['only' => 'read']);
    }

    /**
     * 报名管理系统首页
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        self::$data = $request->session()->get('user_info');

        // 刚登录进来的时候设置该session, 后续表格信息的读取会依据该字段
        if (!$request->session()->has('current_dept')) {
            // 如果是核心部门, 就默认读取所有的部门信息
            $currentDept = $request->session()->has('is_admin') ? 'all' : array_get(self::$data, 'dept_id');

            $request->session()->set('current_dept', $currentDept);
        }

        // 载入首页所需要的数据
        $this->retrieveDepartmentStructure()->retrieveDepartmentLog();

        // 将当前进行的流程序号保存到会话状态中
        $request->session()->set('current_flow', self::$data['current_flow']);

        return
            view('enroll::dashboard', [
                'title' => '管理系统',
                'department' => $request->session()->get('user_info')
            ])->with('others', array_pull(self::$data, 'dept_structure'));
    }

    /**
     * 载入当前部门对应的报名信息
     *
     * @param DataTableRequest $request
     * @param integer|string   $dept
     * @param JsonResponse     $response
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function read(DataTableRequest $request, $dept, JsonResponse $response)
    {
        $count = 0;
        $failed = false;
        $recycle = false;
        $prefix = $request->session()->get('user_info.org_name');

        // 部门模型
        $department = null;

        if (isset($dept)) {
            // 设置当前读取的部门ID
            $request->session()->set('current_dept', $dept);

            // 如果不是'all', 就查找对应部门的数据
            if (is_numeric($dept)) {
                $department = DepartmentStructures::where('org_name', '=', $prefix)->find($dept);

                if (is_null($department)) {
                    $failed = true;
                    // 读取当前登录账户的部门
                    $request->session()->replace(['current_dept' => $request->session()->get('user_info.dept_id')]);
                }
            }

            // 回收站模式
            if ($dept === 'recycle') {
                $recycle = true;
                $request->session()->set('recycle_control', uniqid('recKey/'));

                if (!$request->session()->has('is_admin'))
                    $department = DepartmentStructures::where( // 非核心部门的读取自己部门的淘汰名单
                        'org_name', '=', $prefix
                    )->find($request->session()->get('user_info.dept_id'));
            } else {
                // 删除回收站SessionID
                $request->session()->forget('recycle_control');
            }

            if (!$failed) {
                $deptName = $prefix . '|' . (is_null($department) ? '%' : $department->getAttributeValue('dept_name'));

                // 分页控制
                $length = $request->json('length');
                $cursor = $request->json('start') / $length + 1;

                $count = $this->buildTableResponse(
                    $request, $this->collectApplyData($request, $deptName, $recycle, $length, $cursor)
                );
            }
        }

        return $response->setData([
            'data' => self::$data, 'recordsTotal' => $count['total'], 'recordsFiltered' => $count['filter']
        ]);
    }

    /**
     * 响应用户的查询要求
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function notify(Request $request)
    {
        $form = false;
        $condition = $request->only(['name', 'code']);

        if (empty($condition))
            return response()->json(['status' => -20, 'content' => '填写信息不完整, 请补全后再试']);

        // 表明是否从报名表单处提交
        if ($request->session()->token() === $request->header('X-CSRF-TOKEN')) $form = true;

        $student = (new Sinnjinn())->getStudentByCode($condition);

        if (empty($student->getAttributes()))
            return response()->json(['status' => 0, 'content' => '该学生没有报过任何部门!']);

        $applyData = $student->withApply()->getQuery()->get(['dept_name', 'current_step'])->toArray();
        
        if ($form)
            return response()->json(['status' => 0, 'content' => '该学生已经报过一些部门了。', 'extra' => array_map(function ($v) {
                return $v = explode('|', $v['dept_name']);
            }, $applyData)]);

        return response()->json(['status' => 0, 'content' => '已经成功找到你所登记部门的流程状态。', 'extra' => $applyData]);
    }

    /**
     * 根据表格传输过来的json信息, 收集相应名单数据
     *
     * @param Request $request
     * @param string  $department
     * @param boolean $recycle
     * @param integer $paginate
     * @param integer $cursor
     *
     * @return ApplyData
     */
    protected function collectApplyData(Request $request, $department, $recycle, $paginate, $cursor)
    {
        $apply = new ApplyData();
        $filter = false;

        // 需要搜索内容
        if ($request->input('searching') == true) {
            $val = $request->input('search.value');
            $cols = $request->input('search.columns');

            $filter = function (\Illuminate\Database\Eloquent\Builder $query) use ($cols, $val) {
                $i = 0;
                $count = count($cols);

                while($i < $count)
                    $query->orWhere($cols[$i++], 'LIKE', '%' . $val . '%');
            };
        }

        return $apply->getDepartmentApplyDataWithPager($department, $recycle, $cursor, $paginate, $filter);
    }

    /**
     * 组装回传的Json信息
     *
     * @param Request              $request
     * @param LengthAwarePaginator $data
     *
     * @return array
     */
    protected function buildTableResponse(Request $request, LengthAwarePaginator $data)
    {
        if ($data->isEmpty()) {
            // 返回一个初始的空数组
            self::$data = self::$records;
        }

        /**
         * @var ApplyData $item
         * @var StudentInformation $byUser
         */
        foreach ($data as $index => $item) {
            $tmp = self::$records;
            $student = $item->withUser()->first();

            if (($index = $item->getAttributeValue('current_step')) < 0) {
                $flowName = self::$flown[(-$index)] . '未通过';
            } else {
                $flowName = '通过' . self::$flown[$index - 1];
            }

            if ($item->getAttributeValue('was_send_sms') == 1)
                $flowName .= ' <span class="label label-sm label-success">已发送</span>';

            if (!array_walk(self::$records, function ($v, $k, Sinnjinn $student) use ($flowName) {
                // 排除掉checkbox那一列
                if (is_null($v))
                    return ;
                if ($v == 'circuit_status')
                    self::$records['status'] = $flowName;
                if (null == ($attr = $student->getAttribute($v)))
                    return ;

                self::$records[$k] = $attr;
            }, $student));

            self::$records['checkboxes'] =
                "<label class='mt-checkbox mt-checkbox-single mt-checkbox-outline'>"
                . "<input name='id[". $item->getAttributeValue('enroll_id') ."]'  type='checkbox' class='checkboxes'>"
                . "<span></span></label>";
            self::$records['intention'] = last(explode('|', $item->getAttributeValue('dept_name')));

            self::$data[] = self::$records;
            self::$records = $tmp;
            // 手动计数
        }

        return ['total' => $data->total(), 'filter' => $data->total()];
    }

    /**
     * 获取用户所属部门的日志信息
     *
     * @return $this
     */
    protected function retrieveDepartmentLog()
    {
        $flow = 'current_flow';
        $currentFlow = [];

        // 核心部门的人员可以读取组织下所有部门的当前流程信息
        $target =
            isset(self::$data['is_admin']) ?
                 self::$data['dept_structure'] : [array_only(self::$data, 'dept_id')];

        foreach ($target as $index => $dept) {
            $deptID = array_get($dept, 'dept_id');
            $deptLog = (new DepartmentLog())->getDepartmentLog($deptID);

            if (!is_null($deptLog)) // 保存当前正在进行流程的详细信息
                $currentFlow[$deptID] = unserialize(array_get($deptLog->toArray(), $flow));
        }

        self::$data = array_merge(self::$data, [$flow => $currentFlow]);

        return $this;
    }

    /**
     * 获取用户所在组织的其他部门信息。
     * 如果用户属于核心部门,则会将所有基础部门的数据全部归类于其他部门下
     *
     * @return $this
     */
    protected function retrieveDepartmentStructure()
    {
        $orgName = array_pull(self::$data, 'org_name');

        $deptStructure = (new DepartmentStructures())->getTypedDepartments(
            $orgName, DepartmentStructures::BASE_DEPARTMENT, ['dept_id', 'dept_name']
        )->toArray();

        self::$data['dept_structure'] =
            isset(self::$data['is_admin']) ?  // 如果登录用户属于核心部门, 则不需要排除
                $deptStructure : array_filter($deptStructure, function ($v) {
            return !($v['dept_id'] == self::$data['dept_id']); // 排除用户所在的部门
        });
        
        return $this;
    }
}
