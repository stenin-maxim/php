<?php

namespace App\Http\Requests\Recycler;

use App\Traits\JsonResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UploadRecyclerProductionSpotPhotoRequest extends FormRequest
{
    use JsonResponseTrait;

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'recycler_production_spot_id' => 'integer',
            'main_photo' => 'nullable|image|max:10000', // max 10000kb
            'photos' => 'nullable|array',
            'photos.*' => 'nullable|image|max:10000', // max 10000kb
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException($this->jsonValidationErrorResponse($validator->errors()));
    }

}
