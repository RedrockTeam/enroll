<?php

namespace App\Modules\Enroll\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentLog extends Model
{
    /**
     * @inheritDoc
     */
    protected $connection = 'apollo';

    /**
     * @inheritDoc
     */
    protected $table = 'department_log';

    /**
     * @inheritDoc
     */
    protected $primaryKey = 'dept_log_id';

    /**
     * @inheritDoc
     */
    public $timestamps = true;

    /**
     * 获取当前部门日志
     *
     * @param integer $id
     * @param array   $scope
     *
     * @return DepartmentLog|null
     */
    public function getDepartmentLog($id, $scope = ['total_flown_sms', 'current_flow'])
    {
        return $this->select($scope)->where('which_having', '=', $id)->orderBy('in_year', 'desc')->first();
    }

    /**
     * 初始化部门日志信息
     *
     * @param integer $id
     * @param array   $step
     * @param integer $totalSMS
     * @param string  $by
     *
     * @return void
     */
    public function setDepartmentLog($id, array $step, $totalSMS, $by)
    {
        return $this->insert([
            'total_flown_sms' => $totalSMS,
            'current_flow' => serialize($step),
            'can_enroll' => 1, // 开启报名
            'sms_template' => $step['remark'], // 发送的短信模板
            'which_having' => $id,
            'who_write' => $by,
            'in_year' => date('Y-m-d', time())
        ]);
    }

    /**
     * 保存当前部门所进行的环节流程
     *
     * @param integer $id
     * @param array   $step
     *
     * @return DepartmentLog
     */
    public function setDepartmentCurrentStep($id, array $step)
    {
        return $this->where('which_having', '=', $id)->update(['current_flow' => serialize($step), 'can_enroll' => 0]);
    }
}
