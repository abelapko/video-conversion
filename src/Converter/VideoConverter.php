<?php

namespace App\Converter;

class VideoConverter
{
    public function convert($inputFilePath, $outputFilePath)
    {
        // Простая конвертация видео с использованием ffmpeg
        $command = "ffmpeg -i {$inputFilePath} -c:v libx264 -preset fast -crf 22 {$outputFilePath}";
        exec($command);
    }
}
