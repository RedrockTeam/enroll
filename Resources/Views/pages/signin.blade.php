@extends('vendor.metronic.layouts.global')

@section('layout-css')
    <link href="{{ URL::asset('assets/css/metronic/pages/login.css') }}" rel="stylesheet" type="text/css" />
@endsection

{{-- Body Theme Class ============ --}}
@section('body-class', 'login')

@section('body')
    @section('container')
        <!-- BEGIN LOGO -->
        <div class="logo">
            <a href="index.html">
                <img src="../assets/pages/img/logo-big-white.png" style="height: 17px;" alt="" />
            </a>
        </div>
        <!-- END LOGO -->
        <!-- BEGIN LOGIN -->
        <div class="content">
            <!-- BEGIN LOGIN FORM -->
            @if($only_password)
                <form class="login-form" action="{{ URL::to($module['prefix'] . '/auth/login?state=refresh&next=index&user=' . old('user')) }}" method="post">
            @else
                <form class="login-form" action="{{ URL::to($module['prefix'] . '/auth/login?state=normal&next=index') }}" method="post">
            @endif
                <div class="form-title">
                    <span class="form-title">欢迎</span>
                    <span class="form-subtitle">请登录后操作</span>
                </div>
                @if(count($errors))
                    @foreach($errors->all() as $message)
                        <div class="alert alert-danger">
                            <button class="close" data-close="alert"></button>
                            <span> {{ $message }} </span>
                        </div>
                    @endforeach
                @endif
                @unless($only_password)
                    <div class="form-group">
                        <!--ie8, ie9 does not support html5 placeholder, so we just show field title for that-->
                        <label class="control-label visible-ie8 visible-ie9">用户名</label>
                        <input class="form-control form-control-solid placeholder-no-fix" type="text" autocomplete="off" placeholder="用户名" name="username" value="{{ old('username') }}" />
                    </div>
                @endunless
                <div class="form-group">
                    <label class="control-label visible-ie8 visible-ie9">密码</label>
                    <input class="form-control form-control-solid placeholder-no-fix" type="password" autocomplete="off" placeholder="密码" name="password" />
                </div>
                <div class="form-actions">
                    {{ csrf_field() }}
                    <button type="submit" class="btn red btn-block uppercase">登录</button>
                </div>
                <div class="form-actions">
                    <div class="pull-left">
                        <label class="rememberme mt-checkbox mt-checkbox-outline">
                            <input type="checkbox" name="remember" value="1" @if(old('remember') !== null) {{ 'checked' }} @endif/> 记住我
                            <span></span>
                            @if($only_password)<input type="hidden" name="remember_token" value="{{ $remember_token }}">@endif
                        </label>
                    </div>
                    <div class="pull-right forget-password-block">
                        <a href="javascript:;" id="forget-password" class="forget-password">忘记密码?</a>
                    </div>
                </div>
            </form>
            <!-- END LOGIN FORM -->
            @section('footer')
                <!-- BEGIN FOOTER -->
                <div class="copyright hide"> 本网站由<a href=""> 红岩网校工作站 </a>负责开发，管理，不经红岩网校委员会书面同意，不得进行转载 </div>
                <!-- END FOOTER -->
            @endsection
        </div>
        <!-- END LOGIN -->
    @endsection
@endsection


