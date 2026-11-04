<?php

namespace App\Http\Requests\Products;

use App\Enums\ProductCategory;
use App\Services\Products\SupportedDomainValidator;
use App\Services\Products\UrlSanitizerService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    private ?string $normalizedUrl = null;

    private ?string $canonicalDomain = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $rawUrl = is_string($this->input('url')) ? trim($this->input('url')) : '';

        if ($rawUrl !== '') {
            $this->normalizedUrl = app(UrlSanitizerService::class)->sanitize($rawUrl);
            $this->canonicalDomain = app(SupportedDomainValidator::class)->resolveCanonicalDomain($rawUrl);
        }

        $this->merge([
            'url' => $rawUrl,
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
            'url' => [
                'required',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
                        $fail('Informe uma URL válida.');

                        return;
                    }

                    $user = $this->user();
                    $maxProducts = (int) config('monitoring.max_products_per_user', 20);

                    if ($user !== null && $user->products()->count() >= $maxProducts) {
                        $fail("Você atingiu o limite máximo de {$maxProducts} produtos monitorados.");

                        return;
                    }

                    if ($this->canonicalDomain === null || $this->normalizedUrl === null) {
                        $fail('O domínio informado ainda não é suportado pelo sistema.');

                        return;
                    }

                    if (
                        $user !== null
                        && $user->products()->where('normalized_url', $this->normalizedUrl)->exists()
                    ) {
                        $fail('Este produto já está sendo monitorado na sua conta.');
                    }
                },
            ],
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
            'url' => 'URL do produto',
            'target_price' => 'preço alvo',
            'nickname' => 'apelido',
            'category' => 'categoria',
            'notify_on_stock' => 'alerta de retorno ao estoque',
        ];
    }

    public function normalizedUrl(): string
    {
        return (string) $this->normalizedUrl;
    }

    public function canonicalDomain(): string
    {
        return (string) $this->canonicalDomain;
    }
}
