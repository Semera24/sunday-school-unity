<?php

namespace App\Support;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateMediaPreview
{
    private const MIME_TYPES = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];

    public function supports(Media $media): bool
    {
        return in_array($media->mime_type, self::MIME_TYPES, true)
            && $media->size <= (int) config('media.preview_max_bytes');
    }

    public function response(Media $media): StreamedResponse
    {
        $disk = Storage::disk($media->disk);
        abort_unless($disk->exists($media->path), 404);
        $source = $disk->readStream($media->path);
        abort_unless(is_resource($source), 404);
        $snapshot = tmpfile();
        if ($snapshot === false) {
            fclose($source);
            abort(503, 'Preview is temporarily unavailable.');
        }

        try {
            $maximum = (int) config('media.preview_max_bytes');
            $copied = stream_copy_to_stream($source, $snapshot, $maximum + 1);
            abort_if($copied === false, 503, 'Unable to read preview.');
            abort_if($copied > $maximum, 415, 'Download this file instead.');
            $path = stream_get_meta_data($snapshot)['uri'];
            $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
            abort_unless(in_array($mimeType, self::MIME_TYPES, true), 415, 'Preview is unavailable for this file type.');
            $expectedHash = $media->metadata['sha256'] ?? null;
            if ($expectedHash !== null) {
                abort_unless(is_string($expectedHash) && hash_equals($expectedHash, hash_file('sha256', $path)), 409, 'File integrity check failed.');
            }
            rewind($snapshot);
        } catch (\Throwable $exception) {
            fclose($snapshot);
            throw $exception;
        } finally {
            fclose($source);
        }

        return response()->stream(function () use ($snapshot): void {
            try {
                fpassthru($snapshot);
            } finally {
                fclose($snapshot);
            }
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => (string) $copied,
            'Content-Disposition' => HeaderUtils::makeDisposition('inline', $media->original_name, 'document'),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "sandbox; default-src 'none'; frame-ancestors 'self'",
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
