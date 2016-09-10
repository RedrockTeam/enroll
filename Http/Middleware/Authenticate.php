<?php

namespace App\Modules\Enroll\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Http\Middleware\Authenticate as ProxyAuthenticate;

class Authenticate extends ProxyAuthenticate
{

    /**
     * 登录路径
     *
     * @var string
     */
    protected $loginPath = '/enroll/login';

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @param  string                   $guard
     * @param bool                      $proxy
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = 'enroll', $proxy = true)
    {
        $auth = Auth::guard($guard);

        // 如果用户登录凭证(存放在Cookie中)超时失效
        if (!$request->hasCookie('enroll_master_credential')) {
            // 如果选择了"Remember Me"选项, 才进入无用户名登录状态
            if (!$request->hasCookie($auth->getRecallerName())) {
                $request->session()->clear(); // 清除登录保存的会话状态

                return parent::handle($request, $next, $guard, ['to' => $this->loginPath]);
            }

            // 生成刷新"假登出"状态的Token
            $token = $request->session()->hasOldInput('verify') ? $request->old('verify') : uniqid('tm0Key/', true);

            return redirect()->guest(
                $this->loginPath . '?state=timeout&next=refresh&code=' . $token
            )->withInput(['verify' => $token, 'user' => $auth->id()]);
        }

        return $next($request);
    }
}
