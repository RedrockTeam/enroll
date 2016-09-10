<?php

namespace App\Modules\Enroll\Http\Middleware;

use Closure;

class HoldRequestStatus
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
        $token = $request->session()->get('user_info.dept_id') . $request->session()->getId();

        // 验证API访问请求是否有效
        if (!$request->session()->has('is_admin'))
            if (!empty($request->header('ER-CONTROL-ID')))
                if (!(app('hash')->check($token, $request->header('ER-CONTROL-ID'))))
                    return redirect()->guest('/enroll/auth/logout');

        // 保证Cookie在API有访问的情况下不失效
        if ($request->hasCookie('enroll_master_credential'))
            cookie('enroll_master_credential', $request->cookie('enroll_master_credential'), 30);

        return $next($request);
    }
}
