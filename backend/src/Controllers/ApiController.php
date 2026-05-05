<?php

require_once __DIR__ . '/../Models/SessionStore.php';

class ApiController
{
    private SessionStore $store;

    public function __construct()
    {
        $this->store = new SessionStore(__DIR__ . '/../../storage/data.json');
    }

    public function createTask(): void
    {
        $input = $this->jsonInput();
        $text = trim($input['text'] ?? '');

        if ($text === '') {
            throw new InvalidArgumentException('Task text is required');
        }

        $task = $this->store->saveTask($text);
        $this->respond(['task' => $task]);
    }

    public function startSession(): void
    {
        $input = $this->jsonInput();
        $task = trim($input['task'] ?? '');
        $mode = $input['mode'] ?? 'focus';

        if ($task === '') {
            throw new InvalidArgumentException('Task is required');
        }

        $session = $this->store->startSession($task, $mode);
        $this->respond(['session' => $session]);
    }

    public function completeSession(): void
    {
        $input = $this->jsonInput();
        $task = trim($input['task'] ?? '');
        $mode = $input['mode'] ?? 'focus';
        $duration = (int) ($input['duration'] ?? 0);

        if ($task === '') {
            throw new InvalidArgumentException('Task is required');
        }

        if ($duration <= 0) {
            throw new InvalidArgumentException('Duration must be greater than zero');
        }

        $session = $this->store->completeSession($task, $mode, $duration);
        $this->respond(['session' => $session, 'stats' => $this->store->stats()]);
    }

    public function stats(): void
    {
        $this->respond($this->store->stats());
    }

    private function jsonInput(): array
    {
        $input = json_decode(file_get_contents('php://input'), true);
        return is_array($input) ? $input : [];
    }

    private function respond(array $payload): void
    {
        echo json_encode($payload);
    }
}
