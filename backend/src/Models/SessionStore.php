<?php

class SessionStore
{
    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->ensureStorage();
    }

    public function saveTask(string $text): array
    {
        $data = $this->read();
        $task = [
            'id' => uniqid('task_', true),
            'text' => $text,
            'createdAt' => date(DATE_ATOM),
        ];

        $data['activeTask'] = $task;
        $this->write($data);

        return $task;
    }

    public function startSession(string $task, string $mode): array
    {
        $data = $this->read();
        $session = [
            'id' => uniqid('session_', true),
            'task' => $task,
            'mode' => $this->normalizeMode($mode),
            'status' => 'started',
            'startedAt' => date(DATE_ATOM),
        ];

        $data['currentSession'] = $session;
        $this->write($data);

        return $session;
    }

    public function completeSession(string $task, string $mode, int $duration): array
    {
        $data = $this->read();
        $session = [
            'id' => uniqid('session_', true),
            'task' => $task,
            'mode' => $this->normalizeMode($mode),
            'duration' => $duration,
            'status' => 'done',
            'completedAt' => date(DATE_ATOM),
        ];

        $data['sessions'][] = $session;
        $data['currentSession'] = null;
        $this->write($data);

        return $session;
    }

    public function stats(): array
    {
        $data = $this->read();
        $completed = array_filter($data['sessions'], fn ($session) => ($session['status'] ?? '') === 'done');
        $focusTime = array_reduce($completed, function (int $total, array $session): int {
            if (($session['mode'] ?? '') !== 'focus') {
                return $total;
            }

            return $total + (int) ($session['duration'] ?? 0);
        }, 0);

        return [
            'totalSessions' => count($completed),
            'totalFocusTime' => $focusTime,
        ];
    }

    private function ensureStorage(): void
    {
        $directory = dirname($this->filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (!file_exists($this->filePath)) {
            $this->write($this->emptyData());
        }
    }

    private function read(): array
    {
        $data = json_decode(file_get_contents($this->filePath), true);
        return is_array($data) ? array_merge($this->emptyData(), $data) : $this->emptyData();
    }

    private function write(array $data): void
    {
        file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function emptyData(): array
    {
        return [
            'activeTask' => null,
            'currentSession' => null,
            'sessions' => [],
        ];
    }

    private function normalizeMode(string $mode): string
    {
        return $mode === 'break' ? 'break' : 'focus';
    }
}
