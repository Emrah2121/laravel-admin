<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UploadDocumentService
{
    protected array $allowedTypes = [];

    public function __construct()
    {
        $standardImage = [
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
            'max_size' => 2048,
        ];

        $pdf5MB = [
            'extensions' => ['pdf'],
            'mime_types' => ['application/pdf'],
            'max_size' => 5120,
        ];

        $this->allowedTypes = [
            // Images
            'avatar' => $standardImage,
            'blog_images' => $standardImage,

            // Documents
            'certificate_pdf' => $pdf5MB,
            'post_pdf' => $pdf5MB,
        ];
    }

    protected function validateFile(
        UploadedFile $file,
        string $collectionName
    ): void {
        if (!$file->isValid()) {
            throw ValidationException::withMessages([
                'file' => trans('backend.messages.error.upload'),
            ]);
        }

        $rules = $this->allowedTypes[$collectionName] ?? null;

        if (!$rules) {
            throw ValidationException::withMessages([
                'file' => trans('backend.messages.error.upload_no_rule'),
            ]);
        }

        $maxSize = $rules['max_size'] ?? null;

        if ($maxSize && $file->getSize() > ($maxSize * 1024)) {
            throw ValidationException::withMessages([
                'file' => trans('backend.messages.error.upload_size'),
            ]);
        }

        $extension = strtolower(
            $file->getClientOriginalExtension()
        );

        if (
            !in_array(
                $extension,
                $rules['extensions'],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'file' => trans('backend.messages.error.upload_format', ['extension' => ".{$extension}"]),
            ]);
        }

        $mimeType = $file->getMimeType();

        if (
            !in_array(
                $mimeType,
                $rules['mime_types'],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'file' => trans('backend.messages.error.upload_type'),
            ]);
        }

        if (str_starts_with($mimeType, 'image/')) {
            $imageInfo = @getimagesize($file->getRealPath());

            if ($imageInfo === false) {
                throw ValidationException::withMessages([
                    'file' => trans('backend.messages.error.upload_invalid_image'),
                ]);
            }
        }
    }

    public function upload(
        $model,
        $file,
        $id_model,
        $collectionName,
        $multiple,
        Request $fileRequest,
        $attachment_type = null
    ) {
        try {
            DB::beginTransaction();

            $files = $fileRequest->file($file);

            if (!$files) {
                DB::commit();

                return true;
            }

            if ($multiple) {
                if (!is_array($files)) {
                    $files = [$files];
                }

                foreach ($files as $uploadedFile) {
                    $this->validateFile(
                        $uploadedFile,
                        $collectionName
                    );

                    $document = Storage::disk('documents')->put(
                        $collectionName,
                        $uploadedFile
                    );

                    if (!$document) {
                        throw new \Exception(
                            'Fayl storage-a yazıla bilmədi.'
                        );
                    }
                    Document::create([
                        'manipulationable_type' => $model,
                        'manipulationable_id' => $id_model,
                        'document' => $document,
                        'collection_name' => $collectionName,
                        'attachment_type' => $attachment_type,
                    ]);
                }
            } else {
                $uploadedFile = is_array($files)
                    ? ($files[0] ?? null)
                    : $files;

                if ($uploadedFile) {
                    $this->validateFile(
                        $uploadedFile,
                        $collectionName
                    );

                    $document = Storage::disk('documents')->put(
                        $collectionName,
                        $uploadedFile
                    );

                    if (!$document) {
                        throw new \Exception(
                            'Fayl storage-a yazıla bilmədi.'
                        );
                    }
                    Document::create([
                        'manipulationable_type' => $model,
                        'manipulationable_id' => $id_model,
                        'document' => $document,
                        'collection_name' => $collectionName,
                        'attachment_type' => $attachment_type,
                    ]);
                }
            }

            DB::commit();

            return true;
        } catch (\Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }
    }
}
