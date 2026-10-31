@props(['name', 'label', 'required' => false])
@php($id = 'f_'.str_replace(['.', '[', ']'], '_', $name))
@php($errKey = str_replace(['[', ']'], ['.', ''], $name))
<div class="field">
    <label for="{{ $id }}">{{ $label }} @if($required)<span class="req" aria-hidden="true">*</span>@else <span class="muted small">(optional)</span>@endif</label>
    <label class="file-drop" for="{{ $id }}">
        <span class="file-drop__icon" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 16V4m0 0l-4 4m4-4l4 4M4 16v3a1 1 0 001 1h14a1 1 0 001-1v-3"/></svg>
        </span>
        <span>
            <strong class="small">Choose file</strong><br>
            <span class="file-drop__name" id="{{ $id }}_name">PDF, JPG or PNG · max 5 MB</span>
        </span>
        <input id="{{ $id }}" name="{{ $name }}" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
            data-file-input="{{ $id }}_name" @required($required)>
    </label>
    @error($errKey)<div class="error">{{ $message }}</div>@enderror
</div>
