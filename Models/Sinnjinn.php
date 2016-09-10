<?php

namespace App\Modules\Enroll\Models;

use Illuminate\Database\Eloquent\Model;

class Sinnjinn extends Model
{
    /**
     * @inheritDoc
     */
    protected $connection = 'apollo';

    /**
     * @inheritDoc
     */
    protected $table = 'sinnjinn';

    /**
     * @inheritDoc
     */
    protected $primaryKey = 'user_id';

    /**
     * @inheritDoc
     */
    public $timestamps = false;

    /**
     * 性别转换
     *
     * @var static[]
     */
    protected static $sex = [0 => '男', 1 => '女'];

    /**
     * 用户可以报名多个部门,拥有多条报名信息
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function withApply()
    {
        return $this->hasMany(ApplyData::class, 'user_id', 'user_id');
    }

    /**
     * 通过学号查找报名新生的数据
     *
     * @param array $data
     * @param array $scope
     *
     * @return $this
     */
    public function getStudentByCode(array $data, $scope = ['*'])
    {
        $selected = $this->where(['full_name' => array_first($data), 'student_code' => array_last($data)])->get($scope);

        return empty($selected->all()) ? $this : array_first($selected->all());
    }

    /**
     * 判断用户是否报名过对应组织
     *
     * @param string $organization
     *
     * @return \Illuminate\Support\Collection
     */
    public function getStudentByOrganizationName($organization)
    {
        return $this->whereHas('withApply', function ($query) use ($organization) {
            $query->where('dept_name', 'LIKE', $organization . '|%');
        })->get();
    }

    public function getHavingOrganization()
    {
        return $this->getAttributeValue('having_org') ?: 0;
    }

    /***/
    public function setStudentInformation(array $data)
    {
        foreach ($data as $column => $attribute) {
            if ('code' == $column)
                $this->setAttribute('student_code', $attribute);
            else if ('name' == $column)
                $this->setAttribute('full_name', $attribute);
            else {
                if ('gender' == $column)
                    $attribute = self::$sex[$attribute];

                $this->$column = $attribute;
            }
        }

        return $this->save();
    }
}
