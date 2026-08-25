<?php

namespace App\Http\Requests\PreguntasFrecuentes;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\PreguntasFrecuentes\CreatePostRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return (new CreatePostRequest())->rules();
    }
}