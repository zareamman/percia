<?php
namespace TMDBImporter\Core\Traits;

trait Hookable
{
    protected static array $registeredHooks = [];

    protected function addAction(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        add_action($hook, $callback, $priority, $acceptedArgs);
        $this->registeredHooks[] = ['type' => 'action', 'hook' => $hook];
    }

    protected function addFilter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        add_filter($hook, $callback, $priority, $acceptedArgs);
        $this->registeredHooks[] = ['type' => 'filter', 'hook' => $hook];
    }

    protected function removeAction(string $hook, callable $callback, int $priority = 10): void
    {
        remove_action($hook, $callback, $priority);
    }

    protected function removeFilter(string $hook, callable $callback, int $priority = 10): void
    {
        remove_filter($hook, $callback, $priority);
    }

    protected function doAction(string $hook, ...$args): void
    {
        do_action($hook, ...$args);
    }

    protected function applyFilters(string $hook, mixed $value, ...$args): mixed
    {
        return apply_filters($hook, $value, ...$args);
    }

    public function getRegisteredHooks(): array
    {
        return $this->registeredHooks;
    }
}