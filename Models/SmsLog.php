<?php

namespace App\Modules\Enroll\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    /**
     * @inheritDoc
     */
    protected $connection = 'apollo';

    /**
     * @inheritDoc
     */
    protected $table = 'sms_log';

    /**
     * @inheritDoc
     */
    protected $primaryKey = 'sms_id';

    /**
     * @inheritDoc
     */
    public $timestamps = false;

    /**
     * 记录短信发送日志
     *
     * @param integer $department
     * @param array   $data
     * @param string  $admin
     * @param integer $total
     *
     * @return bool
     */
    public function setSendLog($department, array $data, $admin, $total)
    {
        // 需要记录的信息
        $this->setRawAttributes([
            'dept_id' => $department,
            'total_send' => $total,
            'who_send' => $admin,
            'detail' => json_encode($data, true)
        ]);

        return $this->performInsert($this->newQuery());
    }
}
