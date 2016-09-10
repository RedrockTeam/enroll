<?php

namespace App\Modules\Enroll\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Pvdt\Models\StudentInformation;
use App\Modules\Pvdt\Models\DepartmentStructures;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class Oyabun extends Model implements AuthenticatableContract
{
    use Authenticatable;

    /**
     * @inheritDoc
     */
    protected $connection = 'apollo';

    /**
     * @inheritDoc
     */
    protected $table = 'oyabun';

    /**
     * @inheritDoc
     */
    protected $primaryKey = 'user_id';

    /**
     * @inheritDoc
     */
    public $timestamps = false;

    /**
     * @inheritDoc
     */
    protected $hidden = ['password'];

    /**
     * @inheritDoc
     */
    protected $fillable = ['username', 'password'];

    /**
     * 该用户属于是某组织内部的管理
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function withDepartment()
    {
        return $this->belongsTo(DepartmentStructures::class, 'ref_id', 'dept_id');
    }
}
