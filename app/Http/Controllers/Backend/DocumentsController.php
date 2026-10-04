<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentsController extends Controller
{
    public function deleteDocument(Document $document)
    {
        try {
            Storage::disk('documents')->delete($document->getAttributes()['document']);
            $document->delete();

            return response(['status' => 1]);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return response(['status' => 0]);
        }
    }

    public function setStatus(Document $document)
    {
        try {
            Document::whereNull('parent_id')
                ->where('manipulationable_type', $document->manipulationable_type)
                ->where('manipulationable_id', $document->manipulationable_id)
                ->update(['status' => null]);

            $document->status = 'main';
            $document->save();

            return response(['status' => 1]);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return response(['status' => 0]);
        }
    }
}
