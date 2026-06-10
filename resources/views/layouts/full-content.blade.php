@section('content')
    <section class="content">
        @include('admin::partials.alerts')
        @include('admin::partials.exception')

        {!! $content !!}

        @include('admin::partials.toastr')
    </section>
@endsection

@section('app')
    {!! Dcat\Admin3\Admin::asset()->styleToHtml() !!}

    <div class="content-body" id="app">
        {{-- 页面埋点--}}
        {!! admin_section(Dcat\Admin3\Admin::SECTION['APP_INNER_BEFORE']) !!}

        @yield('content')

        {{-- 页面埋点--}}
        {!! admin_section(Dcat\Admin3\Admin::SECTION['APP_INNER_AFTER']) !!}
    </div>

    {!! Dcat\Admin3\Admin::asset()->scriptToHtml() !!}
    <div class="extra-html">{!! Dcat\Admin3\Admin::html() !!}</div>
@endsection


@if(!request()->pjax())
    @include('admin::layouts.full-page', ['header' => $header])
@else
    <title>{{ Dcat\Admin3\Admin::title() }} @if($header) | {{ $header }}@endif</title>

    <script>Dcat.wait();</script>

    {!! Dcat\Admin3\Admin::asset()->cssToHtml() !!}
    {!! Dcat\Admin3\Admin::asset()->jsToHtml() !!}

    @yield('app')
@endif
