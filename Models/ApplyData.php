<?php

namespace App\Modules\Enroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

use App\Modules\Pvdt\Models\StudentInformation;

class ApplyData extends Model
{
    /**
     * @inheritDoc
     */
    protected $connection = 'apollo';

    /**
     * @inheritDoc
     */
    protected $table = 'apply_data_ex';

    /**
     * @inheritDoc
     */
    protected $primaryKey = 'enroll_id';

    /**
     * @inheritDoc
     */
    public $timestamps = false;

    /**
     * 关联到用户信息表
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function withUser()
    {
        return $this->belongsTo(Sinnjinn::class, 'user_id', 'user_id');
    }

    /**
     * 获取某个部门当前流程下的报名数据, 可分页
     *
     * @param string           $department
     * @param boolean          $recycle
     * @param int              $page
     * @param bool|integer     $perPage
     * @param bool|callable    $filter
     * @param array            $scope
     *
     * @return $this|\Illuminate\Support\Collection
     */
    public function getDepartmentApplyDataWithPager($department, $recycle, $page, $perPage = false, $filter = false, $scope = ['*'])
    {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $this->where([
            ['dept_name', 'LIKE', $department], ['current_step', $recycle ? '<' : '>', 0]
        ]);

        if ($filter)
            $query = $query->whereHas('withUser', $filter);

        if ($perPage != false)
            return $query->paginate($perPage, $scope, 'enroll_data', $page);

        return $query->orderBy('current_step', 'desc')->get($scope);
    }
}
