{{-- Reusable labeled input that restores old input and shows its validation error. --}}
@props(['label','name','value'=>'','type'=>'text'])
<label class="field"><span>{{ $label }}</span><input type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $value) }}" {{ $attributes }}>@error($name)<small class="field-error">{{ $message }}</small>@enderror</label>