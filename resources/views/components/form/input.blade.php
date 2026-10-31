@props(['name', 'label', 'type' => 'text', 'required' => false, 'hint' => null, 'value' => null])
@php($id = 'f_'.str_replace(['.', '[', ']'], '_', $name))
<div class="field">
    <label for="{{ $id }}">{{ $label }} @if($required)<span class="req" aria-hidden="true">*</span>@endif</label>
    @if($type === 'password')
        <div class="pw-wrap">
            <input id="{{ $id }}" name="{{ $name }}" type="password" @required($required)
                {{ $attributes->class(['input', 'is-invalid' => $errors->has($name)]) }}
                @error($name) aria-invalid="true" aria-describedby="{{ $id }}_err" @enderror>
            <button type="button" class="pw-toggle" data-pw-toggle="{{ $id }}" aria-pressed="false" aria-label="Show password">Show</button>
        </div>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @required($required)
            {{ $attributes->class(['input', 'is-invalid' => $errors->has($name)]) }}
            @error($name) aria-invalid="true" aria-describedby="{{ $id }}_err" @enderror>
    @endif
    @if($hint)<div class="hint">{{ $hint }}</div>@endif
    @error($name)<div class="error" id="{{ $id }}_err">{{ $message }}</div>@enderror
</div>
