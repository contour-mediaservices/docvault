<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| DOCVAULT FILE VIEWER
| entscheidet automatisch welcher Viewer verwendet wird
|--------------------------------------------------------------------------
*/

function docvaultViewerUrl(string $fileUrl, string $fileType): string
{
    if ($fileType === 'application/pdf') {
        return "/docvault/pdfjs/web/viewer.html?file=" . urlencode($fileUrl);
    }

    return $fileUrl;
}