<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePrescriptionPrintSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage prescription print settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'active_paper_key' => ['required', 'string', Rule::in(['A4', 'Legal'])],
            'letterhead_height' => ['required', 'string', 'max:20'],
            'font_size_base' => ['required', 'integer', 'min:8', 'max:24'],
            'font_size_vitals' => ['required', 'integer', 'min:8', 'max:24'],
            'font_size_clinical_scans' => ['required', 'integer', 'min:8', 'max:24'],
            'font_size_medicines' => ['required', 'integer', 'min:8', 'max:24'],
            'font_size_medicine_dose' => ['required', 'integer', 'min:8', 'max:24'],
            'paper_presets' => ['required', 'array'],
            'paper_presets.A4' => ['required', 'array'],
            'paper_presets.Legal' => ['required', 'array'],
            'paper_presets.A4.page_size' => ['required', 'string', 'max:20'],
            'paper_presets.A4.orientation' => ['required', 'string', Rule::in(['portrait', 'landscape'])],
            'paper_presets.A4.width' => ['required', 'string', 'max:20'],
            'paper_presets.A4.min_height' => ['required', 'string', 'max:20'],
            'paper_presets.A4.margin_top' => ['required', 'string', 'max:20'],
            'paper_presets.A4.margin_right' => ['required', 'string', 'max:20'],
            'paper_presets.A4.margin_bottom' => ['required', 'string', 'max:20'],
            'paper_presets.A4.margin_left' => ['required', 'string', 'max:20'],
            'paper_presets.Legal.page_size' => ['required', 'string', 'max:20'],
            'paper_presets.Legal.orientation' => ['required', 'string', Rule::in(['portrait', 'landscape'])],
            'paper_presets.Legal.width' => ['required', 'string', 'max:20'],
            'paper_presets.Legal.min_height' => ['required', 'string', 'max:20'],
            'paper_presets.Legal.margin_top' => ['required', 'string', 'max:20'],
            'paper_presets.Legal.margin_right' => ['required', 'string', 'max:20'],
            'paper_presets.Legal.margin_bottom' => ['required', 'string', 'max:20'],
            'paper_presets.Legal.margin_left' => ['required', 'string', 'max:20'],
        ];
    }
}
