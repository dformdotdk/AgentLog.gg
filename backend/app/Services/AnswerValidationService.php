<?php

namespace App\Services;

class AnswerValidationService
{
    public function isCorrect(array $validation, string $answer): bool
    {
        $type = $validation['type'] ?? 'string_set';
        $a = trim(mb_strtolower($answer));

        return match ($type) {
            'numeric' => $this->numeric($validation, $a),
            'string_set' => $this->stringSet($validation, $a),
            'multi' => $this->multi($validation, $a),
            default => $this->stringSet($validation, $a),
        };
    }

    private function numeric(array $v, string $a): bool
    {
        $correct = $v['correct'][0] ?? null;
        if ($correct === null) return false;

        $tol = (float)($v['tolerance'] ?? 0);
        $num = $this->toFloat($a);
        if ($num === null) return false;

        return abs($num - (float)$correct) <= $tol;
    }

    private function stringSet(array $v, string $a): bool
    {
        $correct = $v['correct'] ?? [];
        $normalized = array_map(fn($x)=>trim(mb_strtolower((string)$x)), $correct);
        return in_array($a, $normalized, true);
    }

    private function multi(array $v, string $a): bool
    {
        // MVP format: "key=value;key=value"
        $parts = $v['parts'] ?? [];
        $pairs = [];
        foreach (explode(';', $a) as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '' || !str_contains($chunk, '=')) continue;
            [$k,$val] = array_map('trim', explode('=', $chunk, 2));
            $pairs[mb_strtolower($k)] = $val;
        }

        foreach ($parts as $p) {
            $key = mb_strtolower($p['key'] ?? '');
            if ($key === '' || !isset($pairs[$key])) return false;

            $subType = $p['type'] ?? 'numeric';
            if ($subType === 'numeric') {
                $correct = $p['correct'][0] ?? null;
                if ($correct === null) return false;
                $tol = (float)($p['tolerance'] ?? 0);

                $num = $this->toFloat($pairs[$key]);
                if ($num === null) return false;
                if (abs($num - (float)$correct) > $tol) return false;
            } else {
                $correct = $p['correct'] ?? [];
                $norm = array_map(fn($x)=>trim(mb_strtolower((string)$x)), $correct);
                if (!in_array(trim(mb_strtolower($pairs[$key])), $norm, true)) return false;
            }
        }

        return true;
    }

    private function toFloat(string $s): ?float
    {
        $s = str_replace(',', '.', $s);
        if (!is_numeric($s)) return null;
        return (float)$s;
    }
}
