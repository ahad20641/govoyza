@if (session('status'))
    <div class="alert alert-success" role="status">{{ session('status') }}</div>
@endif
@if ($errors->any() && $errors->count() > 1)
    <div class="alert alert-error" role="alert">Please check the highlighted fields below.</div>
@endif
