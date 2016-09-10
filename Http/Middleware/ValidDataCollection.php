<?php

namespace App\Modules\Enroll\Http\Middleware;

use Closure;

class ValidDataCollection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $oldData = $request->all();
        $newData = ['columns' => [], 'searching' => false];

        if (empty($oldData)) return $next($request);

        // 是否需要使用搜索功能
        if ($oldData['search']['value'] != '') {
            $newData['searching'] = true;
            $newData['search'] = $oldData['search'];
        }

        foreach ($oldData['columns'] as $key => $set) {
            $newData['columns'][] = ['name' => $set['name'], 'data' => $set['data']];

            // 当需要使用搜索功能的时候
            // 只将开启了搜索功能的列名加入到数据中，方便查找
            if ($newData['searching'] && $set['searchable'] === true)
                $newData['search']['columns'][] = $set['name'];
        }

        $request->replace(array_merge($oldData, $newData));

        return $next($request);
    }
}
