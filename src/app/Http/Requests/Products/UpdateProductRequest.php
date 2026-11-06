<?php

namespace App\Http\Requests\Products;

use App\Enums\ProductCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'notify_on_stock' => $this->boolean('notify_on_stock'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'target_price' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'nickname' => ['nullable', 'string', 'max:120'],
            'category' => ['required', Rule::enum(ProductCategory::class)],
            'notify_on_stock' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'target_price' => 'preço alvo',
            'nickname' => 'apelido',
            'category' => 'categoria',
            'notify_on_stock' => 'alerta de retorno ao estoque',
        ];
    }
}
