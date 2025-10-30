<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImageHelper
{
    /**
     * Comprimi e ridimensiona un'immagine mantenendola sotto i 2MB
     * 
     * @param UploadedFile $file
     * @param string $path Percorso di storage (es: 'listings')
     * @param int $maxWidth Larghezza massima (default: 1920px)
     * @param int $maxHeight Altezza massima (default: 2560px)
     * @param int $maxSizeMB Dimensione massima in MB (default: 2MB)
     * @return string Percorso dell'immagine salvata
     */
    public static function compressAndStore(UploadedFile $file, string $path, int $maxWidth = 1920, int $maxHeight = 2560, int $maxSizeMB = 2): string
    {
        // Se il file è già piccolo (< 1MB), salvalo direttamente
        if ($file->getSize() < 1024 * 1024) {
            return $file->store($path, 'public');
        }

        // Leggi l'immagine
        $imagePath = $file->getRealPath();
        $imageInfo = getimagesize($imagePath);
        
        if (!$imageInfo) {
            Log::error('ImageHelper: File immagine non valido', [
                'file_path' => $imagePath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);
            throw new \Exception('File immagine non valido');
        }

        [$originalWidth, $originalHeight, $imageType] = $imageInfo;

        // Calcola le nuove dimensioni mantenendo le proporzioni
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);

        // Se l'immagine è più piccola dei limiti, non ridimensionarla
        if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
        }

        // Crea l'immagine dalla sorgente
        try {
            $sourceImage = match($imageType) {
                IMAGETYPE_JPEG => imagecreatefromjpeg($imagePath),
                IMAGETYPE_PNG => imagecreatefrompng($imagePath),
                IMAGETYPE_GIF => imagecreatefromgif($imagePath),
                IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($imagePath) : throw new \Exception('WebP non supportato'),
                default => throw new \Exception('Tipo immagine non supportato'),
            };
        } catch (\Exception $e) {
            Log::error('ImageHelper: Errore nella creazione immagine dalla sorgente', [
                'image_type' => $imageType,
                'error' => $e->getMessage(),
                'file_path' => $imagePath
            ]);
            throw new \Exception('Errore nella lettura dell\'immagine: ' . $e->getMessage());
        }

        if (!$sourceImage) {
            Log::error('ImageHelper: Impossibile creare immagine dalla sorgente', [
                'image_type' => $imageType,
                'file_path' => $imagePath
            ]);
            throw new \Exception('Impossibile creare immagine dalla sorgente');
        }

        // Crea una nuova immagine ridimensionata
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        // Mantieni la trasparenza per PNG
        if ($imageType === IMAGETYPE_PNG) {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Ridimensiona l'immagine
        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        // Genera nome file univoco
        $extension = match($imageType) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_WEBP => 'webp',
            default => $file->getClientOriginalExtension(),
        };

        $fileName = \Illuminate\Support\Str::uuid() . '.' . $extension;
        $fullPath = "public/{$path}/{$fileName}";
        $tempPath = sys_get_temp_dir() . '/' . $fileName;

        // Comprimi progressivamente fino a raggiungere i 2MB
        $quality = 90;
        $maxSizeBytes = $maxSizeMB * 1024 * 1024;
        
        do {
            // Salva temporaneamente per verificare la dimensione
            match($imageType) {
                IMAGETYPE_JPEG => imagejpeg($resizedImage, $tempPath, $quality),
                IMAGETYPE_PNG => imagepng($resizedImage, $tempPath, 9 - (int)($quality / 10)), // PNG usa 0-9
                IMAGETYPE_GIF => imagegif($resizedImage, $tempPath),
                IMAGETYPE_WEBP => function_exists('imagewebp') ? imagewebp($resizedImage, $tempPath, $quality) : imagejpeg($resizedImage, $tempPath, $quality), // Fallback a JPEG se WebP non supportato
                default => throw new \Exception('Tipo immagine non supportato'),
            };

            $fileSize = filesize($tempPath);
            
            // Se è ancora troppo grande, riduci la qualità
            if ($fileSize > $maxSizeBytes && $quality > 50) {
                $quality -= 10;
            } else {
                break;
            }
        } while ($quality > 50);

        // Se è ancora troppo grande dopo la compressione, ridimensiona ulteriormente
        if ($fileSize > $maxSizeBytes) {
            $scaleFactor = sqrt($maxSizeBytes / $fileSize);
            $newWidth = (int)($newWidth * $scaleFactor);
            $newHeight = (int)($newHeight * $scaleFactor);
            
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            if ($imageType === IMAGETYPE_PNG) {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
            }
            imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
            
            match($imageType) {
                IMAGETYPE_JPEG => imagejpeg($resizedImage, $tempPath, 85),
                IMAGETYPE_PNG => imagepng($resizedImage, $tempPath, 6),
                IMAGETYPE_GIF => imagegif($resizedImage, $tempPath),
                IMAGETYPE_WEBP => function_exists('imagewebp') ? imagewebp($resizedImage, $tempPath, 85) : imagejpeg($resizedImage, $tempPath, 85), // Fallback a JPEG se WebP non supportato
                default => throw new \Exception('Tipo immagine non supportato'),
            };
        }

        // Salva nel storage
        Storage::disk('public')->put("{$path}/{$fileName}", file_get_contents($tempPath));

        // Pulisci risorse
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
        @unlink($tempPath);

        return "{$path}/{$fileName}";
    }
}

