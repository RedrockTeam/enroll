<h3 class="block">完善部门的第{num}轮环节描述</h3>
<div class="form-group">
    <label class="control-label col-md-4">环节类型
        <span class="required"> * </span>
    </label>
    <div class="col-md-6">
        <select class="form-control" name="type">
            <option value="1">笔试</option>
            <option value="2">面试</option>
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
        <input type="text" class="form-control" name="location"/>
        <span class="help-block"> 环节开展的地点 </span>
    </div>
</div>
<div class="form-group">
    <label class="control-label col-md-4">短信模板
        <span class="required"> * </span>
    </label>
    <div class="col-md-6">
        <textarea class="form-control" name="remark" ></textarea>
        <span class="help-block"> 本轮通过时所发送的短信模板 </span>
    </div>
</div>