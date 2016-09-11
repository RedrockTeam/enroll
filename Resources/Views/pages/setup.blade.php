@extends('vendor.metronic.layouts.global')

{{-- Extra Meta --}}
@section('extra-meta')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('layout-css')
    @parent
    <link href="{{ URL::asset('assets/css/metronic/pages/setup.css') }}" rel="stylesheet" type="text/css" />
@endsection

{{-- Body Theme Class ============ --}}
@section('body-class', 'setup')

@section('container')
    <div class="container">
        <div class="content center-wrap" id="enroll_setup" style="overflow: visible;">
            <form class="form-horizontal" action="#" id="circuit_design" method="POST">
                <div class="form-wizard">
                    <div class="form-body">
                        <ul class="nav nav-pills nav-justified steps">
                            <li>
                                <a href="#tab1" data-toggle="tab" class="step active">
                                    <span class="number"> 1 </span>
                                    <span class="desc"><i class="fa fa-check"></i> 报名环节 </span>
                                </a>
                            </li>
                            <li>
                                <a href="#tab2" data-toggle="tab" class="step">
                                    <span class="number"> 2 </span>
                                    <span class="desc"><i class="fa fa-check"></i> 第一轮环节 </span>
                                </a>
                            </li>
                            <li>
                                <a href="#tab3" data-toggle="tab" class="step">
                                    <span class="number"> 3 </span>
                                    <span class="desc"><i class="fa fa-check"></i> 第二轮环节 </span>
                                </a>
                            </li>
                            <li>
                                <a href="#tab-last" data-toggle="tab" class="step">
                                    <span class="number"> - </span>
                                    <span class="desc"><i class="fa fa-check"></i> 保存方案 </span>
                                </a>
                            </li>
                        </ul>
                        <div id="bar" class="progress progress-striped" role="progressbar">
                            <div class="progress-bar progress-bar-success"> </div>
                        </div>
                        <div class="tab-content">
                            <div class="tab-pane active" id="tab1" data-step="1">
                                <h3 class="block">完善部门的报名环节描述</h3>
                                <div class="form-group">
                                    <label class="control-label col-md-4">环节类型
                                        <span class="required"> * </span>
                                    </label>
                                    <div class="col-md-6">
                                        <select class="form-control" name="type">
                                            <option value="0">报名</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-4">开启时间
                                        <span class="required"> * </span>
                                    </label>
                                    <div class="col-md-6">
                                        <input type="date" class="form-control" name="time" />
                                        <span class="help-block"> 当前流程预计开启的时间, 注意:上一个流程会自动结束 </span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-4">所在地点
                                        <span class="required"> * </span>
                                    </label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="location" />
                                        <span class="help-block"> 环节开展的地点 </span>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane active" id="tab2" data-step="2"></div>
                            <div class="tab-pane active" id="tab3" data-step="3"></div>
                            <div class="tab-pane active center-body" id="tab-last">
                                <h3 class="block">确认你的部门招新流程</h3>
                                <h4 class="form-section">1</h4>
                                <h4 class="form-section">2</h4>
                                <h4 class="form-section">3</h4>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <div class="row">
                            <div class="col-md-offset-3 col-md-9">
                                <a href="javascript:;" class="btn green button-submit" style="display: none">
                                    <i class="fa fa-check"></i> 提交环节设计
                                </a>
                                <a href="javascript:;" class="btn green button-plus" style="display: none">
                                    <i class="fa fa-plus"></i> 增加环节
                                </a>
                                <a href="javascript:;" class="btn btn-outline green button-next">
                                    <i class="fa fa-angle-right"></i> 下一个环节
                                </a>
                                <a href="javascript:;" class="btn default button-previous" style="display: none">
                                    <i class="fa fa-angle-left"></i> 返回上一个
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('plugins-js')
    <!-- BEGIN PAGE LEVEL PLUGINS -->
    <script src="{{ URL::asset('assets/js/vendor/bootstrap-wizard/bootstrap_wizard.js') }}" type="text/javascript"></script>
    <!-- END PAGE LEVEL PLUGINS -->
@endsection

@section('theme-js')
    <!-- BEGIN PAGE LEVEL SCRIPTS -->
    <script src="{{ URL::asset('assets/js/enroll/vendor.js') }}" type="text/javascript"></script>
    <!-- END PAGE LEVEL SCRIPTS -->
@endsection