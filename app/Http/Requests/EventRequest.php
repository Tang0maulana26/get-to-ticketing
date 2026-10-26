<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->role === 'admin'; }
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'], 'title' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'alpha_dash:ascii', 'max:180', Rule::unique('events')->ignore($this->route('event'))],
            'summary' => ['required', 'string', 'max:300'], 'description' => ['required', 'string', 'max:20000'],
            'venue' => ['required', 'string', 'max:160'], 'city' => ['required', 'string', 'max:80'], 'address' => ['required', 'string', 'max:300'],
            'start_time' => ['required', 'date'], 'end_time' => ['nullable', 'date', 'after:start_time'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])], 'featured' => ['boolean'],
            'banner_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=6000,max_height=6000'],
            'gallery' => ['nullable', 'array', 'max:6'], 'gallery.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=6000,max_height=6000'],
            'remove_images' => ['nullable', 'array'], 'remove_images.*' => ['integer'],
            'image_positions' => ['nullable', 'array'], 'image_positions.*' => ['integer', 'min:0', 'max:99'],
        ];
    }
}
