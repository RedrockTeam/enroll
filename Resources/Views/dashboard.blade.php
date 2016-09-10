@extends('vendor.metronic.layouts.global')

{{-- Extra Meta --}}
@section('extra-meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="bm-id" content="{{ Session::get('current_dept')  }}">
<meta name="control-key" content="{{ bcrypt(Session::get('is_admin') . Session::getId()) }}">
<meta name="control-dept-id" content="{{ bcrypt(Session::get('user_info.dept_id') . Session::getId()) }}">
@endsection

{{-- Plugin Stylesheets ============ --}}
@section('plugins-css')
<!-- BEGIN PAGE LEVEL PLUGINS -->
<link href="{{ URL::asset('assets/css/vendor/datatable/datatable.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('assets/css/vendor/bootstrap-modal/extend_modal.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('assets/css/vendor/bootstrap-select/bootstrap-select.css') }}" rel="stylesheet" type="text/css" />
<style type="text/css">
    .dt-buttons { display: none; }
</style>
<!-- END PAGE LEVEL PLUGINS -->
@endsection

{{-- Body Theme Class ============ --}}
@section('body-class', 'page-md')

@section('body')
    @section('container')
    	<!-- BEGIN CONTAINER -->
        <div class="container-fluid" style="overflow-x: hidden;">
        	<div class="page-content page-content-popup">
                {{-- Must be handle parent view composer --}}
                @parent
                {{-- Content include main content ============ --}}
                @section('content')
            	<div class="page-fixed-main-content" style="margin-left: 0">
            		{{-- Main Content --}}
                    <div class="row">
                        <div class="col-md-12">
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption font-dark">
                                        <span class="caption-subject bold uppercase">报名信息管理</span>
                                    </div>
                                    <div class="actions">
                                        <a class="btn btn-circle btn-icon-only btn-default fa fa-sign-out" href="javascript:;"></a>
                                        <a class="btn btn-circle btn-icon-only btn-default fullscreen" href="javascript:;"> </a>
                                    </div>
                                </div>
                                <div class="portlet-body">
                                    <div class="table-toolbar">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="btn-group" data-filter-control="enroll" data-filter-col="报名部门">
                                                    <button class="btn red" data-department-id="all">全部</button>
                                                    @if(!Session::has('is_admin'))
                                                        <button class="btn blue" data-department-id="{{ $department['dept_id'] }}">{{ $department['dept_name'] }}</button>
                                                    @endif
                                                    <div class="btn-group btn-group-solid">
                                                        <button type="button" class="btn green dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                                            其他部门 <i class="fa fa-angle-down"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            @foreach($others as $other)
                                                                <li><a href="javascript:;" data-other-id="{{ $other['dept_id'] }}">{{ $other['dept_name'] }}</a></li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                    <button class="btn dark" id="recycle">回收站<i class="fa fa-recycle"></i></button>
                                                </div>
                                                <div class="btn-group">
                                                    <a class="btn btn-default sbold" data-toggle="modal" href="#enroll-form-modal">添加报名<i class="fa fa-plus"></i></a>
                                                </div>
                                                <div class="btn-group">
                                                    <a class="btn btn-default sbold" id="checkout" href="##">切换到下一流程</a>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="btn-group pull-right">
                                                    <button class="btn dark btn-outline dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
                                                        工具<i class="fa fa-angle-down"></i>
                                                    </button>
                                                    <ul class="dropdown-menu pull-right">
                                                        <li><a class="buttons-print" href="javascript:;"><i class="fa fa-print"></i>打印表单</a></li>
                                                        <li><a class="buttons-csv buttons-html5" href="javascript:;"><i class="fa fa-file-excel-o"></i>导出至Excel</a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-container">
                                        <div class="table-actions-wrapper">
                                            <div class="btn-group">
                                                <a class="btn btn-sm dark" href="javascript:;" data-toggle="dropdown" aria-expanded=false>
                                                    流程操作 <i class="fa fa-angle-down"></i>
                                                </a>
                                                <ul class="dropdown-menu">
                                                    @if(Session::get('current_dept') == Session::get('user_info.dept_id'))
                                                        <li>
                                                            <a class="table-submit-type" href="javascript:;"> <i class="fa fa-check"></i> 选择通过 </a>
                                                        </li>
                                                        <li>
                                                            <a class="table-send-sms" href="javascript:;"> <i class="fa fa-send"></i> 发送短信 </a>
                                                        </li>
                                                        <li class="divider"></li>
                                                    @endif
                                                    <li>
                                                        <a href="##" class="table-selected-info"><i class="fa fa-exclamation"></i> 暂无人员选中</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <table class="table table-striped table-bordered table-hover table-checkable" id="enroll" width="100%">
                                            <thead>
                                            <tr role="row" class="heading">
                                                <th data-data="checkboxes" data-name="checkboxes">
                                                    <label class="mt-checkbox mt-checkbox-single mt-checkbox-outline">
                                                        <input type="checkbox" class="group-checkable" data-set="#enroll .checkboxes" />
                                                        <span></span>
                                                    </label>
                                                </th>
                                                <th data-data="name" data-name="full_name"> 姓名 </th>
                                                <th data-data="gender" data-name="gender"> 性别 </th>
                                                <th data-data="code" data-name="student_code"> 学号 </th>
                                                <th data-data="college" data-name="college" > 学院 </th>
                                                <th data-data="phone" data-name="contact" > 联系方式 </th>
                                                <th data-data="intention" data-name="intention" > 报名部门 </th>
                                                <th data-data="status" data-name="circuit_status" > 当前状态 </th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr class="odd gradeX"> </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="waiting">
                                </div>
                            </div>
                        </div>
                    </div>
            	</div>
                @endsection
                {{-- Footer include website copyright ============ --}}
                @section('footer')
                <div id="enroll-form-modal" class="modal fade" tabindex="-1" data-width="420" data-backdrop="static" data-keyboard="false">
                    <form role="form" method="POST" action="{{ URL::to('/enroll/api/create') }}" name="add-role" id="add-role">
                        <div class="form-body">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                                <h4 class="modal-title">添加报名</h4>
                                {{ csrf_field() }}
                            </div>
                            <div class="modal-body">
                                <div class="form-group form-md-line-input form-md-floating-label">
                                    <div class="input-icon">
                                        <input type="text" name="name" class="form-control" autocomplete="name" autofocus required>
                                        <label for="form-control">请输入姓名</label>
                                        <span class="help-block">请在此输入需要添加的学生姓名</span>
                                        <i class="icon-user"></i>
                                    </div>
                                </div>
                                <div class="form-group form-md-line-input form-md-floating-label">
                                    <select name="gender" class="form-control" required>
                                        <option selected>...</option>
                                        <option value="0">男</option>
                                        <option value="1">女</option>
                                        <option value="2">其他性别</option>
                                    </select>
                                    <label for="form-control">请输入性别</label>
                                </div>
                                <div class="form-group form-md-line-input form-md-floating-label">
                                    <div class="input-icon">
                                        <input type="number" name="code" class="form-control" autocomplete="on" pattern="20[1-9]{2}\d{6}" required>
                                        <label for="form-control">请输入学号</label>
                                        <span class="help-block">请在此输入需要添加的学生学号</span>
                                        <i class="fa fa-at"></i>
                                    </div>
                                </div>
                                <div class="form-group form-md-line-input form-md-floating-label">
                                    <div class="input-icon">
                                        <input type="text" name="college" class="form-control" autocomplete="organization" required>
                                        <label for="form-control">请输入学院</label>
                                        <span class="help-block">请在此输入需要添加的学生学院</span>
                                        <i class="icon-graduation"></i>
                                    </div>
                                </div>
                                <div class="form-group form-md-line-input form-md-floating-label">
                                    <div class="input-icon">
                                        <input type="tel" name="contact" class="form-control" autocomplete="tel" required>
                                        <label for="form-control">请输入联系方式</label>
                                        <span class="help-block">请在此输入需要添加的学生的联系方式（手机号或者QQ号等）</span>
                                        <i class="icon-phone"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" data-dismiss="modal" class="btn dark btn-outline">取消</button>
                                <button type="submit" class="btn red" form="add-role">保存</button>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- BEGIN FOOTER -->
                <p class="copyright-v2">
                    本网站由<a href=""> 红岩网校工作站 </a>负责开发，管理，不经红岩网校委员会书面同意，不得进行转载
                </p>
                <a href="#index" class="go2top">
                    <i class="icon-arrow-up"></i>
                </a>
                <!-- END FOOTER -->
                @endsection
            </div>
        </div>
    @endsection
@endsection

@section('plugins-js')
<!-- BEGIN PAGE LEVEL PLUGINS -->
<script src="{{ URL::asset('assets/js/vendor/datatable/datatable.js') }}" type="text/javascript"></script>
<script src="{{ URL::asset('assets/js/vendor/datatable/datatable.buttons.js') }}" type="text/javascript"></script>
<script src="{{ URL::asset('assets/js/vendor/bootstrap-select/bootstrap_select.js') }}" type="text/javascript"></script>
<script src="{{ URL::asset('assets/js/vendor/bootstrap-modal/extend_modal.js') }}" type="text/javascript"></script>
<!-- END PAGE LEVEL PLUGINS -->
@endsection

@section('theme-js')
<!-- BEGIN PAGE LEVEL SCRIPTS -->
<script src="{{ URL::asset('assets/js/metronic/datatable.js') }}" type="text/javascript"></script>
<script src="{{ URL::asset('assets/js/enroll/vendor.js') }}" type="text/javascript"></script>
<!-- END PAGE LEVEL SCRIPTS -->
@endsection