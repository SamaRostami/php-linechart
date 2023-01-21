<?php

declare(strict_types=1);

namespace OndrejVrto\LineChart;

use stdClass;
use Stringable;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Collection;

final class LineChart implements Stringable {
    use LineChartSetters;

    /** @var Collection<int,float> */
    private Collection $cleanData;

    /** @param array<mixed> $data */
    public function __construct(
        private readonly array $data
    ) {
    }

    /** @param array<mixed> $data */
    public static function new(array $data): self {
        return new self($data);
    }

    public function make(): string {
        $this->cleanInputData();

        $widthRaw = $this->resolveWidth();
        $heightRaw = $this->resolveHeight();

        $id = Uuid::uuid4()->toString();

        $points = $this->resolvePoints();
        $colors = $this->resolveColors();

        $widthSvg = $this->widthSvg;
        $heightSvg = $this->heightSvg;
        $strokeWidth = $this->strokeWidth;

        $widthScale = round(($widthSvg - $strokeWidth) / $widthRaw, 4);
        $heightScale = round(($heightSvg - $strokeWidth) / $heightRaw, 4);

        $widthTranslate = round($strokeWidth / ($widthScale * 2), 4);
        $heightTranslate = round(($strokeWidth / ($heightScale * 2)) + $heightRaw, 4);

        ob_start();
        include __DIR__.'/LineChart.view.php';
        $svg = ob_get_contents();
        ob_end_clean();

        return is_string($svg) ? $svg : '';
    }

    public function __toString(): string {
        return $this->make();
    }

    private function cleanInputData(): void {
        $tmp = collect($this->data)
            ->flatten()
            ->filter(fn ($value) => is_numeric($value) || (is_string($value) && ctype_digit($value)) || is_bool($value));

        $count = $tmp->count();

        $tmp = $tmp
            ->when(
                0 === $count,
                fn ($collection) => $collection->push(0, 0)
            )
            ->when(
                1 === $count,
                fn ($collection) => $collection->prepend(0)
            )
            ->map(function ($value): float {
                /** @var int|float|bool|numeric-string $value */
                return (float) $value;
            })
            ->when(
                true === $this->reverseOrder,
                fn ($collection) => $collection->reverse()
            )
            ->unless(
                null === $this->maxItemAmount,
                fn ($collection) => $collection->shift($this->maxItemAmount)
            );

        /** @var float */
        $min = $tmp->min();

        $this->cleanData = $tmp
            ->when(
                $min < 0,
                fn ($collection) => $collection->map(fn ($value) => $value - $min)
            )
            ->values();
    }

    public function resolvePoints(): string {
        return $this->cleanData
            ->map(fn (float $value, int $key): string => sprintf("%d %h", $key, $value))
            ->implode(' ');
    }

    private function resolveWidth(): int {
        /** @var positive-int */
        $maxKey = $this->cleanData->keys()->pop();
        return max(1, (int) $maxKey);
    }

    private function resolveHeight(): int {
        /** @var float */
        $max = $this->lockValueY ?? $this->cleanData->max();

        return max(1, (int) $max);
    }

    /** @return Collection<int,stdClass> */
    private function resolveColors(): Collection {
        $tmp = collect($this->colors)
            ->filter(fn ($value) => 1 === preg_match("/^#([a-f0-9]{6}|[a-f0-9]{3})$/i", $value));

        $count = $tmp->count();

        if (0 === $count) {
            $tmp = collect(['#fbd808', '#ff9005', '#f9530b', '#ff0000']);
            $count = 4;
        }

        $step = 1 === $count
            ? 100
            : 100 / ($count - 1);

        return $tmp
            ->values()
            ->map(function (string $colorString, int $key) use ($step) {
                $color = new stdClass();
                $color->code = mb_strtolower($colorString);
                $color->offset = sprintf("%01.02h", ceil($key * $step) / 100);

                return $color;
            });
    }
}
