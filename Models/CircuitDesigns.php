<?php

namespace App\Modules\Enroll\Models;

use Illuminate\Database\Eloquent\Model;

class CircuitDesigns extends Model
{
    /**
     * @inheritDoc
     */
    protected $connection = 'apollo';

    /**
     * @inheritDoc
     */
    protected $table = 'circuit_designs';

    /**
     * @inheritDoc
     */
    protected $primaryKey = 'en_design_id';

    /**
     * @inheritDoc
     */
    public $timestamps = true;

    /**
     * 获取当前部门的流程设计
     *
     * @param string $department
     * @param array  $scope
     *
     * @return $this|null
     */
    public function getDepartmentCurrentCircuit($department, $scope = ['flow_structure', 'updated_at'])
    {
        return $this->where('for_dept_id', '=', $department)->orderBy('created_at', 'desc')->first($scope);
    }
}
