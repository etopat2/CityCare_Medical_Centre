<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Defines authorization and input validation rules for the profile update request workflow.
 */
class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'avatar' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'extensions:jpg,jpeg,png,webp',
                'max:2048',
                'dimensions:min_width=96,min_height=96,max_width=2000,max_height=2000',
            ],
        ];
    }

    /**
     * Get the validation messages for the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'avatar.image' => 'Choose a valid image file for your profile photo.',
            'avatar.mimes' => 'Profile photos must be JPG, PNG, or WebP images.',
            'avatar.extensions' => 'Use a .jpg, .jpeg, .png, or .webp file extension.',
            'avatar.max' => 'Profile photos must be 2 MB or smaller.',
            'avatar.dimensions' => 'Profile photos must be between 96 and 2000 pixels in each dimension.',
        ];
    }
}
