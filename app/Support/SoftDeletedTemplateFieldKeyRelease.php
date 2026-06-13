<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

class SoftDeletedTemplateFieldKeyRelease
{
    /**
     * Free a template field_key slot occupied by soft-deleted rows.
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function release(
        string $modelClass,
        string $templateForeignKey,
        int $templateId,
        string $fieldKey,
        ?int $exceptId = null,
    ): void {
        $modelClass::onlyTrashed()
            ->where($templateForeignKey, $templateId)
            ->where('field_key', $fieldKey)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->each(function (Model $field) {
                $field->update([
                    'field_key' => $field->field_key.'_archived_'.$field->id,
                ]);
            });
    }
}
