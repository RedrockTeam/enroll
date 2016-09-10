<?php

namespace App\Modules\Enroll\Http\Controllers;

use App\Http\Controllers\Auth\AuthController as Controller;
use App\Modules\Enroll\Models\Oyabun;
use App\Modules\Enroll\Models\User;
use App\Modules\Pvdt\Models\DepartmentStructures;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Validator;

class AuthController extends Controller
{
    /**
     * The Guard name by protected this controller
     *
     * @var string
     */
    protected $guard = 'enroll';

    /**
     * Show the application login form.
     *
     * @var string
     */
    protected $loginView = 'enroll::pages.signin';

    /**
     * @inheritDoc
     */
    protected $redirectTo = '/enroll/dashboard';

    /**
     * Where to redirect users after logout.
     *
     * @var string
     */
    protected $redirectAfterLogout = '/enroll/login';

    /**
     * The login form used this property.
     *
     * @var string
     */
    protected $username = 'username';

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        // 加载中间件
        parent::__construct('enroll', 'toLogout');
    }

    /**
     * @inheritDoc
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'username' => 'required|max:45',
            'password' => 'required|min:6|max:99|confirmed'
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function create(array $data)
    {
        throw new MethodNotAllowedException([], '暂时不允许注册新用户。');
    }

    /**
     * 当用户登录后执行的操作
     *
     * @param Request $request
     * @param Oyabun  $user
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function authenticated(Request $request, Oyabun $user)
    {
        $redirect = app('redirect');
        // 记录用户的登录情况
        $cookie = cookie('enroll_master_credential', uniqid('eMcKey/', true) . '=' . $user->getAttributeValue('username') . uniqid('/'), 30);

        // 绑定session到回调地址上
        $redirect->setSession($this->registerUserSession($request, $user));

        // 跳转到指定的页面
        return $redirect->intended($this->redirectTo)->withCookie($cookie);
    }

    /**
     * 用户登录时执行的操作
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function toLogin(Request $request)
    {
        /** @var SessionGuard $guard */
        $guard = Auth::guard($this->getGuard());

        // 无密码登录
        if ($request->has('remember_token')) {
            $user = $guard->user();/** @var Oyabun $userModel */
            $recaller = $guard->getRecallerName();

            // 如果没有勾选"Remember Me"标志
            if (!$request->has('remember') && $request->hasCookie($recaller)) {
                // 执行方式暂时不理解
                $guard->getCookieJar()->queue($guard->getCookieJar()->forget($recaller));
            }

            // 尝试验证用户密码
            if ($user instanceof Oyabun
                &&
                ($attempt = ($guard->getProvider()->validateCredentials($user, $request->only('password'))))
            ) {
                // 定义自定义的session数据
                return $this->authenticated($request, $user);
            }

            // 用户输入的密码和之前登录的用户不匹配, 简单认为是新的用户登录
            $request->merge(['username' => $user->getAttribute('username')]);
            // 清除以前的输入信息
            $request->flush();
        }

        if (!$guard->viaRemember() || false == $attempt) {
            // 跳转到登出操作的默认行为
            return $this->login($request);
        }
    }

    /**
     * 载入登录用户的会话状态
     *
     * @param Request $request
     * @param Oyabun  $user
     *
     * @return \Illuminate\Session\Store
     */
    protected function registerUserSession(Request $request, Oyabun $user)
    {
        // 如果用户处于某个"直辖部门"之外的地位
        if ($user->getAttributeValue('out_of_dept') == true) {
            $request->session()->set('is_admin', 'yes');
        }

        $department = $user->withDepartment()->getResults();
        /** @var DepartmentStructures $department */

        foreach ($department->toArray() as $property => $value) {
            $request->session()->set('user_info' . '.' . $property, $value);
        }

        return $request->session();
    }

    /**
     * 清除用户登录凭证, 可模拟伪登出状态
     *
     * @param Request $request
     */
    protected function clearLoginCredentials(Request $request)
    {
        // 清空用户的会话状态
        $request->session()->forget(['is_admin', 'user_info', Auth::guard($this->guard)->getName()]);

        // 清除用户的登录凭证
        $request->cookies->remove('enroll_master_credential');
    }

    /**
     * 用户注销时执行的操作
     *
     * @param Request  $request
     * @return Response
     */
    public function toLogout(Request $request)
    {
        $this->clearLoginCredentials($request);

        // 跳转到登出操作的默认行为
        return $this->logout();
    }

    /**
     * 渲染用户登录主页面
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function view(Request $request)
    {
        $view = view($this->loginView, [
            // 包含了页面上的文字元素
            'title' => '后台登录',
        ]);
        $onlyPassword = false; // 该标志用来判断是否需要使用用户名

        // 伪造假登出状态
        if ($request->session()->has('user_info') || $request->session()->hasOldInput('user')) {
            $this->clearLoginCredentials($request);
        }

        if ($request->old('verify', 'invalid_token') == $request->query('code')) {
            // 如果收到的是请求中带有超时参数
            if ($request->query('state') == 'timeout') {
                /**
                 * 如果下一步操作是refresh,则表示用户需要从
                 * 假登出状态恢复,此时需要维持之前登录时获取
                 * 的rememberToken
                 */
                if ($request->query('next') == 'refresh') {
                    $onlyPassword = true;
                    $rememberToken = $request->cookies->get(Auth::guard($this->getGuard())->getRecallerName());

                    // 保持refresh令牌有效直到登录成功
                    $request->session()->reflash();
                    // 设置仅用密码登录的Token
                    $view->with('remember_token', $rememberToken);
                }

                $view->withErrors('Operation time out, please login with your password.');
            }
        }

        return $view->with('only_password', $onlyPassword);
    }
}
