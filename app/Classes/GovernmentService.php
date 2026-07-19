<?php

namespace App\Classes;

interface GovernmentService
{
    public function getName(): string;

    public function getType(): string;

    public function getCountryCodes(): array;

    public function authenticate(array $credentials): mixed;

    public function submit(string $endpoint, array $data): mixed;

    public function getStatus(string $documentId): array;
}
