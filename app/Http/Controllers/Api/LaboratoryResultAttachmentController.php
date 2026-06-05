<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LaboratoryResultAttachmentResource;
use App\Models\LaboratoryResult;
use App\Models\LaboratoryResultAttachment;
use App\Services\LaboratoryResultAttachmentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaboratoryResultAttachmentController extends Controller
{
    public function __construct(
        protected LaboratoryResultAttachmentService $attachmentService
    ) {}

    public function store(Request $request, LaboratoryResult $laboratoryResult)
    {
        $this->authorize('update', $laboratoryResult);

        $validated = $request->validate([
            'file'                        => 'required|file|image|mimes:jpeg,jpg,png,webp,gif,bmp|max:15360',
            'laboratory_result_value_id'  => 'nullable|integer|exists:laboratory_result_values,id',
        ]);

        if (! empty($validated['laboratory_result_value_id'])) {
            $belongs = $laboratoryResult->values()
                ->whereKey($validated['laboratory_result_value_id'])
                ->exists();

            if (! $belongs) {
                return response()->json(['message' => 'The value row does not belong to this result.'], 422);
            }
        }

        $attachment = $this->attachmentService->store(
            $laboratoryResult,
            $request->file('file'),
            $request->user(),
            $validated['laboratory_result_value_id'] ?? null
        );

        return response()->json([
            'message' => 'Image uploaded successfully.',
            'data'    => new LaboratoryResultAttachmentResource($attachment),
        ], 201);
    }

    public function destroy(LaboratoryResult $laboratoryResult, LaboratoryResultAttachment $attachment)
    {
        $this->authorize('update', $laboratoryResult);

        if ((int) $attachment->laboratory_result_id !== (int) $laboratoryResult->id) {
            abort(404);
        }

        $this->attachmentService->delete($attachment);

        return response()->json(['message' => 'Image removed successfully.']);
    }

    public function preview(LaboratoryResult $laboratoryResult, LaboratoryResultAttachment $attachment): BinaryFileResponse
    {
        $this->authorize('view', $laboratoryResult);

        if ((int) $attachment->laboratory_result_id !== (int) $laboratoryResult->id) {
            abort(404);
        }

        $path = $this->attachmentService->resolveFilePath($attachment);

        if (! $path) {
            abort(404, 'Image file not found.');
        }

        return response()->file($path, [
            'Content-Type'        => $attachment->mime_type ?? 'image/jpeg',
            'Content-Disposition' => 'inline; filename="' . ($attachment->original_name ?? 'xray-image') . '"',
            'Cache-Control'       => 'private, max-age=3600',
        ]);
    }
}
