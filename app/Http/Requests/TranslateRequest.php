<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $source
 * @property string $target
 * @property string $name
 * @property string $key
 */
class TranslateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'file' => ['required'],
            'source' => ['required', 'string', 'size:2'],
            'target' => ['required', 'string', 'size:2'],
            'name' => ['required', 'string', 'min:1'],
            'key' => ['required', 'string'],
        ];
    }
}
