@foreach ($fields as $name => $value)
    @php
        $fieldName = is_numeric($name) ? $field : $name;
    @endphp
    <div class="flex flex-col gap-1 mb-5 lg:mb-7">
        <label class="text-sm font-medium text-secondary-foreground">{{ $fieldName }}</label>
        <span class="text-sm font-semibold text-foreground">{{ $value }}</span>
    </div>
@endforeach
