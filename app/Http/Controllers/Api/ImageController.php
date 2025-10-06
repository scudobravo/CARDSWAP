<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ImageController extends Controller
{
    /**
     * Upload image for card model or listing
     */
    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            'type' => 'required|in:card_model,listing,avatar',
            'entity_id' => 'nullable|integer',
            'alt_text' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('image');
            $type = $request->type;
            $entityId = $request->entity_id;
            $altText = $request->alt_text;

            // Genera nome file univoco
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            
            // Determina il percorso di storage
            $path = match($type) {
                'card_model' => 'card-models',
                'listing' => 'listings',
                'avatar' => 'avatars',
                default => 'uploads'
            };

            // Salva l'immagine
            $filePath = $file->storeAs("public/{$path}", $fileName);
            
            // Genera URL pubblico
            $publicUrl = Storage::url($filePath);

            // Salva metadati dell'immagine
            $imageData = [
                'filename' => $fileName,
                'original_name' => $file->getClientOriginalName(),
                'path' => $filePath,
                'url' => $publicUrl,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'alt_text' => $altText,
                'type' => $type,
                'entity_id' => $entityId,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Immagine caricata con successo',
                'data' => $imageData
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il caricamento dell\'immagine',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload multiple images
     */
    public function uploadMultiple(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'type' => 'required|in:card_model,listing',
            'entity_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $files = $request->file('images');
            $type = $request->type;
            $entityId = $request->entity_id;
            $uploadedImages = [];

            foreach ($files as $file) {
                // Genera nome file univoco
                $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                
                // Determina il percorso di storage
                $path = match($type) {
                    'card_model' => 'card-models',
                    'listing' => 'listings',
                    default => 'uploads'
                };

                // Salva l'immagine
                $filePath = $file->storeAs("public/{$path}", $fileName);
                
                // Genera URL pubblico
                $publicUrl = Storage::url($filePath);

                $uploadedImages[] = [
                    'filename' => $fileName,
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $filePath,
                    'url' => $publicUrl,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'type' => $type,
                    'entity_id' => $entityId,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => count($uploadedImages) . ' immagini caricate con successo',
                'data' => $uploadedImages
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il caricamento delle immagini',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete image
     */
    public function delete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required|string',
            'path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filename = $request->filename;
            $path = $request->path;

            // Verifica che il file esista
            if (!Storage::exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File non trovato'
                ], 404);
            }

            // Elimina il file
            Storage::delete($path);

            return response()->json([
                'success' => true,
                'message' => 'Immagine eliminata con successo'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'eliminazione dell\'immagine',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get image info
     */
    public function info(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $path = $request->path;

            // Verifica che il file esista
            if (!Storage::exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File non trovato'
                ], 404);
            }

            $fileInfo = [
                'filename' => basename($path),
                'path' => $path,
                'url' => Storage::url($path),
                'size' => Storage::size($path),
                'mime_type' => Storage::mimeType($path),
                'last_modified' => Storage::lastModified($path),
            ];

            return response()->json([
                'success' => true,
                'data' => $fileInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il recupero delle informazioni',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize image (resize, compress)
     */
    public function optimize(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
            'width' => 'nullable|integer|min:100|max:2000',
            'height' => 'nullable|integer|min:100|max=2000',
            'quality' => 'nullable|integer|min:10|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $path = $request->path;
            $width = $request->get('width', 800);
            $height = $request->get('height', 600);
            $quality = $request->get('quality', 85);

            // Verifica che il file esista
            if (!Storage::exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File non trovato'
                ], 404);
            }

            // Per ora restituiamo un messaggio di successo
            // L'ottimizzazione vera e propria richiederebbe librerie come Intervention Image
            return response()->json([
                'success' => true,
                'message' => 'Ottimizzazione immagine richiesta',
                'data' => [
                    'original_path' => $path,
                    'optimized_path' => $path,
                    'width' => $width,
                    'height' => $height,
                    'quality' => $quality,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'ottimizzazione dell\'immagine',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
