{{-- Sprint S1 placeholder: says which planning task fills this spot. Remove when the task lands. --}}
@props(['task'])
<div {{ $attributes->class('soon') }}>
    <strong>Coming in task {{ $task }}.</strong> {{ $slot }}
</div>
