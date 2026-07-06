<?php

declare(strict_types=1);

namespace App\ViewModels;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

/**
 * @implements Arrayable<string, mixed>
 */
abstract class ViewModel implements Arrayable
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $class = new ReflectionClass($this);
        $array = [];

        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($this->shouldIgnore($method->getName())) {
                continue;
            }

            $array[Str::snake($method->getName())] = $this->{$method->getName()}();
        }

        return $array;
    }

    private function shouldIgnore(string $methodName): bool
    {
        if (str_starts_with($methodName, '__')) {
            return true;
        }

        return in_array($methodName, ['toArray', 'toResponse', 'jsonSerialize'], true);
    }
}
