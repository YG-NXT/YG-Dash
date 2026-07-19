<?php

namespace App\Classes;

class GovernmentIntegrationRegistry
{
    protected array $services = [];

    public function register(string $name, GovernmentService $service, array $countryCodes, string $type = 'general'): void
    {
        $this->services[$name] = [
            'service' => $service,
            'countries' => $countryCodes,
            'type' => $type,
        ];
    }

    public function get(string $name, string $countryCode): ?GovernmentService
    {
        if (isset($this->services[$name])
            && in_array($countryCode, $this->services[$name]['countries'])) {
            return $this->services[$name]['service'];
        }

        return null;
    }

    public function getByType(string $type, string $countryCode): array
    {
        return array_values(array_filter($this->services, function ($service) use ($type, $countryCode) {
            return $service['type'] === $type
                && in_array($countryCode, $service['countries']);
        }));
    }

    public function getForCountry(string $countryCode): array
    {
        return array_values(array_filter($this->services, function ($service) use ($countryCode) {
            return in_array($countryCode, $service['countries']);
        }));
    }

    public function getAll(): array
    {
        return $this->services;
    }

    public function has(string $name, string $countryCode): bool
    {
        return $this->get($name, $countryCode) !== null;
    }

    public function hasType(string $type, string $countryCode): bool
    {
        return !empty($this->getByType($type, $countryCode));
    }
}
