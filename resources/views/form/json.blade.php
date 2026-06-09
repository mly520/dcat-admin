<div class="{{$viewClass['form-group']}}">

    <div class="{{ $viewClass['label'] }} control-label">
        <span>{!! $label !!}</span>
    </div>

    <div class="{{$viewClass['field']}}">

        @include('admin::form.error')

        <textarea
            name="{{$name}}"
            class="form-control dcat-json-field {{$class}}"
            rows="8"
            style="font-family: monospace;"
            {!! $attributes !!}
        >{{ $formatted }}</textarea>

        <button type="button" class="btn btn-xs btn-default mt-1 dcat-json-format" data-target="{{$name}}">
            Format / Validate
        </button>
        <span class="text-danger dcat-json-error" data-target="{{$name}}" style="display:none;margin-left:6px;">
            JSON 格式错误
        </span>

        @include('admin::form.help-block')

    </div>
</div>

<script once>
$(document).off('click', '.dcat-json-format').on('click', '.dcat-json-format', function () {
    var name = $(this).data('target');
    var $ta = $('textarea[name="' + name + '"]');
    var $err = $('.dcat-json-error[data-target="' + name + '"]');
    try {
        var v = $ta.val();
        var obj = (v && v.trim() !== '') ? JSON.parse(v) : null;
        $ta.val(obj === null ? '' : JSON.stringify(obj, null, 2));
        $ta.css('border-color', '');
        $err.hide();
    } catch (e) {
        $ta.css('border-color', 'red');
        $err.show();
    }
});
</script>
