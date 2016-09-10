<?php

namespace App\Modules\Enroll\Http\Requests;

use App\Http\Requests\Request;

class DataTableRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // 替换由中间件过滤的请求数据
        $this->replace($this->request->all());

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'draw' => 'required|integer',
            'length' => 'required|integer|in:14,20,30,50,100',
            'start' => 'required|integer'
        ];
    }
}
