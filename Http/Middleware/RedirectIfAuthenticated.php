<?php

namespace App\Modules\Enroll\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use App\Http\Middleware\RedirectIfAuthenticated as ProxyRedirectIfAuthenticated;

class RedirectIfAuthenticated extends ProxyRedirectIfAuthenticated
{

    /**
     * 应用主页面
     *
     * @var string
     */
    protected $basePath = '/enroll/dashboard';

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @param string                    $guard
     * @param bool                      $proxy
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = 'enroll', $proxy = true)
    {
        // 只有在用户凭证有效的情况下才重定向
        if ($request->hasCookie('enroll_master_credential')) {
            $result = parent::handle($request, $next, $guard, ['back' => $this->basePath]);

            if ($result instanceof RedirectResponse) {
                return $result;
            }
        }

        return $next($request);
    }
}
