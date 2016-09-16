<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>分数录入系统</title>
    <link href="{{ URL::asset('assets/css/vendor/bootstrap/bootstrap.css') }}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="{{ URL::asset('assets/css/score/index.css') }}">
</head>
<body>
    <div class="container-fluid">
        <div class="row-fluid">
            <div class="span12">
                <h3 style="text-align: center">
                   分数录入系统
                </h3>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class="span12">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>
                            <strong>姓名</strong>
                        </th>
                        <th>
                            <strong>学号</strong>
                        </th>
                        <th>
                            <strong>联系方式</strong>
                        </th>
                        <th>
                            <strong>分数</strong>
                        </th>
                        <th>
                            <strong>编辑</strong>
                        </th>
                    </tr>
                </thead>
                <tbody id="table-body">
                    @foreach($data as $index => $item)
                        <tr>
                            <td>{{ $item->full_name }}</td>
                            <td>{{ $item->student_code }}</td>
                            <td>{{ $item->contact }}</td>
                            <td class="score">{{ $item->score }}</td>
                            <td>
                                <button data-id="{{ $item->user_id }}" class="edit-score-btn">编辑分数</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="{{ URL::asset('assets/js/vendor/jquery-bootstrap.js') }}"></script>
<script src="{{ URL::asset('assets/js/score/index.js') }}"></script>
</body>
</html>