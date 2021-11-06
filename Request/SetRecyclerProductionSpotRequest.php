<?php

namespace App\Http\Requests\Recycler;

use App\Traits\JsonResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SetRecyclerProductionSpotRequest extends FormRequest
{
    use JsonResponseTrait;

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|max:20',
            'address' => 'required|max:200',
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
