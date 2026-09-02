<?php

namespace App\Support;

class AgentUploadLimits
{
    public function maxFileKilobytes(): int
    {
        return max(1, (int) config('services.agent_uploads.upload_max_kb', 20 * 1024));
    }

    public function maxRequestKilobytes(): int
    {
        $configuredKilobytes = (int) config('services.agent_uploads.post_max_kb', 30 * 1024);

        return max($this->maxFileKilobytes(), max(1, $configuredKilobytes));
    }

    public function maxFileBytes(): int
    {
        return $this->maxFileKilobytes() * 1024;
    }

    public function maxRequestBytes(): int
    {
        return $this->maxRequestKilobytes() * 1024;
    }

    public function maxFileLabel(): string
    {
        return $this->formatKilobytes($this->maxFileKilobytes());
    }

    public function maxRequestLabel(): string
    {
        return $this->formatKilobytes($this->maxRequestKilobytes());
    }

    /**
     * @return array{max_file_bytes: int, max_request_bytes: int, max_file_label: string, max_request_label: string}
     */
    public function payload(): array
    {
        return [
            'max_file_bytes' => $this->maxFileBytes(),
            'max_request_bytes' => $this->maxRequestBytes(),
            'max_file_label' => $this->maxFileLabel(),
            'max_request_label' => $this->maxRequestLabel(),
        ];
    }

    private function formatKilobytes(int $kilobytes): string
    {
        if ($kilobytes >= 1024) {
            $megabytes = $kilobytes / 1024;
            $formattedMegabytes = fmod($megabytes, 1.0) === 0.0
                ? number_format($megabytes, 0, ',', '.')
                : number_format($megabytes, 2, ',', '.');

            return "{$formattedMegabytes} MB";
        }

        return number_format($kilobytes, 0, ',', '.').' KB';
    }
}
