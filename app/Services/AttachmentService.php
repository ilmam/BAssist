<?php

namespace App\Services;

use App\Models\Attachment;
use App\Support\AttachableSupport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentService
{
    /**
     * @return list<Attachment>
     */
    public function list(string $model, int $id): array
    {
        $record = $this->record($model, $id);

        return $record->attachments()->get()->all();
    }

    public function store(string $model, int $id, UploadedFile $file): Attachment
    {
        $record = $this->record($model, $id);
        $this->assertAllowed($file, 'file');

        return $this->write($record, $file, 'file');
    }

    /**
     * Save new uploads and remove checked files from a parent entity form.
     */
    public function syncFromRequest(string $model, int $id, \Illuminate\Http\Request $request, string $field = 'attachments'): void
    {
        $files = $this->uploadedFiles($request, $field);
        foreach ($files as $file) {
            $this->assertAllowed($file, $field);
        }

        $removeIds = array_values(array_filter(array_map('intval', (array) $request->input('remove_'.$field, []))));
        foreach ($removeIds as $attachmentId) {
            if ($attachmentId > 0) {
                $this->destroy($model, $id, $attachmentId);
            }
        }

        $record = $this->record($model, $id);
        foreach ($files as $file) {
            $this->write($record, $file, $field);
        }
    }

    /**
     * @return list<UploadedFile>
     */
    protected function uploadedFiles(\Illuminate\Http\Request $request, string $field): array
    {
        $uploaded = $request->file($field);
        if ($uploaded instanceof UploadedFile) {
            $uploaded = [$uploaded];
        }

        if (! is_array($uploaded)) {
            return [];
        }

        return array_values(array_filter(
            $uploaded,
            static fn ($file) => $file instanceof UploadedFile && $file->isValid()
        ));
    }

    protected function write(Model $record, UploadedFile $file, string $errorKey = 'file'): Attachment
    {
        $disk = (string) config('attachments.disk', 'local');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $directory = 'attachments/'.Str::snake(class_basename($record)).'/'.$record->getKey();
        $path = $file->storeAs($directory, Str::uuid().($extension !== '' ? '.'.$extension : ''), $disk);

        if (! is_string($path) || $path === '') {
            throw ValidationException::withMessages([
                $errorKey => __('ui.attachments_store_failed'),
            ]);
        }

        return $record->attachments()->create([
            'original_name' => $this->safeName($file->getClientOriginalName()),
            'disk' => $disk,
            'path' => $path,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'created_by' => auth()->id(),
        ]);
    }

    public function download(string $model, int $id, int $attachmentId): StreamedResponse
    {
        $attachment = $this->owned($model, $id, $attachmentId);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    public function destroy(string $model, int $id, int $attachmentId): void
    {
        $this->owned($model, $id, $attachmentId)->delete();
    }

    protected function record(string $model, int $id): Model
    {
        if (! AttachableSupport::enabled($model)) {
            abort(404);
        }

        $class = AttachableSupport::modelClass($model);
        if ($class === null) {
            abort(404);
        }

        $record = $class::query()->find($id);
        if ($record === null) {
            abort(404);
        }

        return $record;
    }

    protected function owned(string $model, int $id, int $attachmentId): Attachment
    {
        $record = $this->record($model, $id);
        $attachment = $record->attachments()->whereKey($attachmentId)->first();
        if ($attachment === null) {
            abort(404);
        }

        return $attachment;
    }

    protected function assertAllowed(UploadedFile $file, string $errorKey = 'file'): void
    {
        $maxKb = (int) config('attachments.max_kilobytes', 10240);
        if ($file->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages([
                $errorKey => __('ui.attachments_too_large', ['max' => $this->maxLabel($maxKb)]),
            ]);
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $allowed = array_map('strtolower', config('attachments.extensions', []));
        if ($extension === '' || ! in_array($extension, $allowed, true)) {
            throw ValidationException::withMessages([
                $errorKey => __('ui.attachments_invalid'),
            ]);
        }
    }

    protected function safeName(string $name): string
    {
        $base = basename(str_replace('\\', '/', $name));
        $base = preg_replace('/[^\w.\- ()]+/u', '_', $base) ?: 'file';

        return Str::limit($base, 240, '');
    }

    protected function maxLabel(int $maxKb): string
    {
        if ($maxKb >= 1024 && $maxKb % 1024 === 0) {
            return ($maxKb / 1024).' MB';
        }

        return $maxKb.' KB';
    }
}
