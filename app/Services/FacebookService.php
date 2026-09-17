<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class FacebookService
{
    private string $graphApiVersion = 'v26.0';

    private string $pageId;

    private string $pageAccessToken;

    public function __construct()
    {
        $this->pageId = config('services.facebook.page_id');
        $this->pageAccessToken = config('services.facebook.page_access_token');
    }

    public function publishPhoto(
        string $imagePath,
        ?string $caption = null
    ): string {
        if (! file_exists($imagePath)) {
            throw new RuntimeException(
                "Image does not exist: {$imagePath}"
            );
        }

        $url = sprintf(
            'https://graph.facebook.com/%s/%s/photos',
            $this->graphApiVersion,
            $this->pageId
        );

        $response = Http::attach(
            'source',
            file_get_contents($imagePath),
            basename($imagePath)
        )->post($url, [
            'access_token' => $this->pageAccessToken,
            'caption' => $caption,
            'published' => true,
        ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Facebook API error: ' . $response->body()
            );
        }

        $data = $response->json();

        if (empty($data['id']) && empty($data['post_id'])) {
            throw new RuntimeException(
                'Facebook API did not return a post ID: ' .
                $response->body()
            );
        }

        return $data['post_id'] ?? $data['id'];
    }
}
