<?php

namespace App\Services;

use App\Models\LaboratoryResult;
use App\Models\LaboratoryResultAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LaboratoryResultAttachmentService
{
    public function store(
        LaboratoryResult $result,
        UploadedFile $file,
        User $user,
        ?int $valueId = null
    ): LaboratoryResultAttachment {
        $directory = "laboratory-results/{$result->id}";
        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin';
        $filename = Str::uuid() . '.' . strtolower($extension);

        $path = $file->storeAs($directory, $filename, 'local');

        return LaboratoryResultAttachment::create([
            'laboratory_result_id'      => $result->id,
            'laboratory_result_value_id' => $valueId,
            'file_path'                 => $path,
            'original_name'             => $file->getClientOriginalName(),
            'mime_type'                 => $file->getMimeType(),
            'file_size'                 => $file->getSize(),
            'created_by'                => $user->id,
        ]);
    }

    public function delete(LaboratoryResultAttachment $attachment): void
    {
        if ($attachment->file_path && Storage::disk('local')->exists($attachment->file_path)) {
            Storage::disk('local')->delete($attachment->file_path);
        }

        $attachment->delete();
    }

    public function resolveFilePath(LaboratoryResultAttachment $attachment): ?string
    {
        if (! $attachment->file_path) {
            return null;
        }

        if (! Storage::disk('local')->exists($attachment->file_path)) {
            return null;
        }

        return Storage::disk('local')->path($attachment->file_path);
    }
}
