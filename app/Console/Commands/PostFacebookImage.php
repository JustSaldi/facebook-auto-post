<?php

namespace App\Console\Commands;

use App\Services\FacebookService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class PostFacebookImage extends Command
{
    protected $signature = 'facebook:post';

    protected $description = 'Post the next image to Facebook';

    public function handle(FacebookService $facebook): int
    {
        $this->info('Facebook poster started.');

        $imageDirectory = public_path('images');

        if (! File::isDirectory($imageDirectory)) {
            $this->error("Image directory does not exist: {$imageDirectory}");

            return self::FAILURE;
        }

        $images = collect(File::files($imageDirectory))
            ->filter(fn ($file) => in_array(
                strtolower($file->getExtension()),
                ['jpg', 'jpeg', 'png', 'webp']
            ))
            ->sortBy(fn ($file) => $file->getFilename())
            ->values();

        if ($images->isEmpty()) {
            $this->error('No images found.');

            return self::FAILURE;
        }

        $baseDate = Carbon::create(2026, 1, 1)->startOfDay();
        $today = now()->startOfDay();

        $dayNumber = $baseDate->diffInDays($today);

        $index = $dayNumber % $images->count();

        $image = $images[$index];

        $captionFile = public_path(
            'images/' .
            pathinfo($image->getFilename(), PATHINFO_FILENAME) .
            '.txt'
        );

        $caption = File::exists($captionFile)
            ? trim(File::get($captionFile))
            : null;

        $this->info("Selected image: {$image->getFilename()}");

        try {
            $postId = $facebook->publishPhoto(
                $image->getRealPath(),
                $caption
            );

            $this->info("Successfully posted {$image->getFilename()}.");
            $this->info("Facebook post ID: {$postId}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            report($e);

            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
