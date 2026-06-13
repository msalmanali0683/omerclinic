<?php

namespace App\Services;

use App\Models\PrescriptionPrintSetting;
use App\Models\User;

class PrescriptionPrintSettingService
{
    public static function defaultPaperPresets(): array
    {
        return [
            'A4' => [
                'label' => 'A4',
                'page_size' => 'A4',
                'orientation' => 'portrait',
                'width' => '210mm',
                'min_height' => '297mm',
                'margin_top' => '0.1in',
                'margin_right' => '0.32in',
                'margin_bottom' => '1.5in',
                'margin_left' => '0.5in',
            ],
            'Legal' => [
                'label' => 'Legal',
                'page_size' => 'Legal',
                'orientation' => 'portrait',
                'width' => '8.5in',
                'min_height' => '14in',
                'margin_top' => '0.1in',
                'margin_right' => '0.32in',
                'margin_bottom' => '1.5in',
                'margin_left' => '0.5in',
            ],
        ];
    }

    public function current(): PrescriptionPrintSetting
    {
        $defaults = [
            'active_paper_key' => 'A4',
            'paper_presets' => self::defaultPaperPresets(),
            'letterhead_height' => '2.45in',
            'font_size_base' => 12,
            'font_size_vitals' => 12,
            'font_size_clinical_scans' => 12,
            'font_size_medicines' => 13,
            'font_size_medicine_dose' => 12,
        ];

        return PrescriptionPrintSetting::query()->firstOrCreate([], $defaults);
    }

    public function resolve(?PrescriptionPrintSetting $settings = null): array
    {
        $settings ??= $this->current();
        $presets = array_replace_recursive(self::defaultPaperPresets(), $settings->paper_presets ?? []);
        $activeKey = array_key_exists($settings->active_paper_key, $presets)
            ? $settings->active_paper_key
            : 'A4';
        $activePreset = $presets[$activeKey];

        return $this->enforcePrintMargins([
            'active_paper_key' => $activeKey,
            'paper_presets' => $presets,
            'letterhead_height' => $settings->letterhead_height,
            'font_size_base' => $settings->font_size_base,
            'font_size_vitals' => $settings->font_size_vitals,
            'font_size_clinical_scans' => $settings->font_size_clinical_scans,
            'font_size_medicines' => $settings->font_size_medicines,
            'font_size_medicine_dose' => $settings->font_size_medicine_dose,
            'page_size' => $activePreset['page_size'],
            'orientation' => $activePreset['orientation'],
            'width' => $activePreset['width'],
            'min_height' => $activePreset['min_height'],
            'margin_top' => $activePreset['margin_top'],
            'margin_right' => $activePreset['margin_right'],
            'margin_bottom' => $activePreset['margin_bottom'],
            'margin_left' => $activePreset['margin_left'],
            'margin' => implode(' ', [
                $activePreset['margin_top'],
                $activePreset['margin_right'],
                $activePreset['margin_bottom'],
                $activePreset['margin_left'],
            ]),
        ]);
    }

    public function update(array $data, User $user): array
    {
        $settings = $this->current();

        $settings->fill([
            'active_paper_key' => $data['active_paper_key'] ?? $settings->active_paper_key,
            'paper_presets' => $data['paper_presets'] ?? $settings->paper_presets,
            'letterhead_height' => $data['letterhead_height'] ?? $settings->letterhead_height,
            'font_size_base' => $data['font_size_base'] ?? $settings->font_size_base,
            'font_size_vitals' => $data['font_size_vitals'] ?? $settings->font_size_vitals,
            'font_size_clinical_scans' => $data['font_size_clinical_scans'] ?? $settings->font_size_clinical_scans,
            'font_size_medicines' => $data['font_size_medicines'] ?? $settings->font_size_medicines,
            'font_size_medicine_dose' => $data['font_size_medicine_dose'] ?? $settings->font_size_medicine_dose,
            'updated_by' => $user->id,
        ]);

        $settings->save();

        return $this->enforcePrintMargins($this->resolve($settings->fresh()));
    }

    protected function enforcePrintMargins(array $settings): array
    {
        $settings['margin_bottom'] = '1.5in';
        $settings['margin'] = implode(' ', [
            $settings['margin_top'],
            $settings['margin_right'],
            '1.5in',
            $settings['margin_left'],
        ]);

        return $settings;
    }
}
