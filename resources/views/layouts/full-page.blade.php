<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="chrome=1,IE=edge">
    {{-- 默认使用谷歌浏览器内核--}}
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">

    <title>@if(! empty($header)){{ $header }} | @endif {{ Dcat\Admin3\Admin::title() }}</title>

    @if(! config('admin.disable_no_referrer_meta'))
        <meta name="referrer" content="no-referrer"/>
    @endif

    @if(! empty($favicon = Dcat\Admin3\Admin::favicon()))
        <link rel="shortcut icon" href="{{$favicon}}">
    @endif

    {!! admin_section(Dcat\Admin3\Admin::SECTION['HEAD']) !!}

    {!! Dcat\Admin3\Admin::asset()->headerJsToHtml() !!}

    {!! Dcat\Admin3\Admin::asset()->cssToHtml() !!}
</head>

<body class="dcat-admin-body full-page {{ $configData['body_class'] }}">

<script>
    var Dcat = CreateDcat({!! Dcat\Admin3\Admin::jsVariables() !!});
</script>

{{-- 页面埋点 --}}
{!! admin_section(Dcat\Admin3\Admin::SECTION['BODY_INNER_BEFORE']) !!}

<div class="app-content content">
    <div class="wrapper" id="{{ $pjaxContainerId }}">
        @yield('app')
    </div>
</div>

{!! admin_section(Dcat\Admin3\Admin::SECTION['BODY_INNER_AFTER']) !!}

{!! Dcat\Admin3\Admin::asset()->jsToHtml() !!}

<script>Dcat.boot();</script>

</body>
</html>