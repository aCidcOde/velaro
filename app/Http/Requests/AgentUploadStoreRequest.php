<?php

namespace App\Http\Requests;

use App\Support\AgentUploadLimits;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class AgentUploadStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $limits = app(AgentUploadLimits::class);
        $maxFileKilobytes = $limits->maxFileKilobytes();
        $maxRequestBytes = $limits->maxRequestBytes();

        return [
            'files' => [
                'required',
                'array',
                'min:1',
                function (string $attribute, mixed $value, Closure $fail) use ($limits, $maxRequestBytes): void {
                    $totalBytes = 0;

                    foreach ($this->file('files', []) as $file) {
                        if ($file instanceof UploadedFile) {
                            $totalBytes += (int) $file->getSize();
                        }
                    }

                    if ($totalBytes > $maxRequestBytes) {
                        $fail(__('O envio total deve ter no maximo '.$limits->maxRequestLabel().'.'));
                    }
                },
            ],
            'files.*' => ['required', 'file', 'mimes:pdf', 'max:'.$maxFileKilobytes],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $limits = app(AgentUploadLimits::class);

        return [
            'files.required' => __('Selecione ao menos um PDF.'),
            'files.array' => __('Envie uma lista valida de arquivos.'),
            'files.min' => __('Selecione ao menos um PDF.'),
            'files.*.file' => __('O arquivo enviado e invalido.'),
            'files.*.mimes' => __('Apenas arquivos PDF sao permitidos.'),
            'files.*.max' => __('Cada PDF deve ter no maximo '.$limits->maxFileLabel().'.'),
        ];
    }
}
