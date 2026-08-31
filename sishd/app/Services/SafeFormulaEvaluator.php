<?php

namespace App\Services;

/**
 * Evaluator ekspresi aritmatika parameterized untuk formula ASB (Bab 9 kajian: "formula ASB
 * sebaiknya dibuat parameterized, bukan hard-coded"). Sengaja TIDAK memakai eval() PHP — ini
 * tokenizer + recursive-descent parser sendiri yang hanya mengizinkan angka, variabel {nama},
 * operator + - * /, dan tanda kurung. Precedence standar: ()  lalu * /  lalu + -.
 *
 * Contoh: evaluate('{luas_bangunan} * {standar_biaya_per_m2}', ['luas_bangunan' => 500, 'standar_biaya_per_m2' => 7500000])
 */
class SafeFormulaEvaluator
{
    /** @var array<int, array{type: string, value: string}> */
    private array $tokens = [];

    private int $pos = 0;

    /**
     * @param  array<string, float|int>  $variables
     *
     * @throws FormulaEvaluationException
     */
    public function evaluate(string $expression, array $variables): float
    {
        $this->tokens = $this->tokenize($expression);
        $this->pos = 0;

        if ($this->tokens === []) {
            throw new FormulaEvaluationException('Formula kosong.');
        }

        $result = $this->parseExpression($variables);

        if ($this->pos < count($this->tokens)) {
            $sisa = $this->tokens[$this->pos]['value'];
            throw new FormulaEvaluationException("Formula tidak valid: token tak terduga \"{$sisa}\".");
        }

        return $result;
    }

    /** @return array<int, array{type: string, value: string}> */
    private function tokenize(string $expression): array
    {
        $tokens = [];
        $length = strlen($expression);
        $i = 0;

        while ($i < $length) {
            $char = $expression[$i];

            if (ctype_space($char)) {
                $i++;

                continue;
            }

            if (in_array($char, ['+', '-', '*', '/', '(', ')'], true)) {
                $tokens[] = ['type' => $char, 'value' => $char];
                $i++;

                continue;
            }

            if ($char === '{') {
                $end = strpos($expression, '}', $i);
                if ($end === false) {
                    throw new FormulaEvaluationException('Formula tidak valid: tanda "{" tidak ditutup.');
                }
                $name = trim(substr($expression, $i + 1, $end - $i - 1));
                if ($name === '' || ! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
                    throw new FormulaEvaluationException("Nama variabel tidak valid: \"{$name}\".");
                }
                $tokens[] = ['type' => 'VAR', 'value' => $name];
                $i = $end + 1;

                continue;
            }

            if (ctype_digit($char) || $char === '.') {
                $j = $i;
                while ($j < $length && (ctype_digit($expression[$j]) || $expression[$j] === '.')) {
                    $j++;
                }
                $numberStr = substr($expression, $i, $j - $i);
                if (! is_numeric($numberStr)) {
                    throw new FormulaEvaluationException("Angka tidak valid: \"{$numberStr}\".");
                }
                $tokens[] = ['type' => 'NUM', 'value' => $numberStr];
                $i = $j;

                continue;
            }

            throw new FormulaEvaluationException("Karakter tidak diizinkan dalam formula: \"{$char}\".");
        }

        return $tokens;
    }

    /** @param array<string, float|int> $variables */
    private function parseExpression(array $variables): float
    {
        $value = $this->parseTerm($variables);

        while (($t = $this->peek()) && in_array($t['type'], ['+', '-'], true)) {
            $op = $this->advance()['type'];
            $rhs = $this->parseTerm($variables);
            $value = $op === '+' ? $value + $rhs : $value - $rhs;
        }

        return $value;
    }

    /** @param array<string, float|int> $variables */
    private function parseTerm(array $variables): float
    {
        $value = $this->parseUnary($variables);

        while (($t = $this->peek()) && in_array($t['type'], ['*', '/'], true)) {
            $op = $this->advance()['type'];
            $rhs = $this->parseUnary($variables);

            if ($op === '/') {
                if (abs($rhs) < 1e-12) {
                    throw new FormulaEvaluationException('Formula tidak valid: pembagian dengan nol.');
                }
                $value /= $rhs;
            } else {
                $value *= $rhs;
            }
        }

        return $value;
    }

    /** @param array<string, float|int> $variables */
    private function parseUnary(array $variables): float
    {
        if (($t = $this->peek()) && in_array($t['type'], ['+', '-'], true)) {
            $op = $this->advance()['type'];

            return $op === '-' ? -$this->parseUnary($variables) : $this->parseUnary($variables);
        }

        return $this->parsePrimary($variables);
    }

    /** @param array<string, float|int> $variables */
    private function parsePrimary(array $variables): float
    {
        $token = $this->peek();

        if (! $token) {
            throw new FormulaEvaluationException('Formula tidak lengkap.');
        }

        if ($token['type'] === 'NUM') {
            $this->advance();

            return (float) $token['value'];
        }

        if ($token['type'] === 'VAR') {
            $this->advance();
            if (! array_key_exists($token['value'], $variables)) {
                throw new FormulaEvaluationException("Variabel \"{$token['value']}\" tidak memiliki nilai.");
            }

            return (float) $variables[$token['value']];
        }

        if ($token['type'] === '(') {
            $this->advance();
            $value = $this->parseExpression($variables);
            $closing = $this->peek();
            if (! $closing || $closing['type'] !== ')') {
                throw new FormulaEvaluationException('Formula tidak valid: tanda kurung tidak seimbang.');
            }
            $this->advance();

            return $value;
        }

        throw new FormulaEvaluationException("Token tak terduga: \"{$token['value']}\".");
    }

    /** @return array{type: string, value: string}|null */
    private function peek(): ?array
    {
        return $this->tokens[$this->pos] ?? null;
    }

    /** @return array{type: string, value: string} */
    private function advance(): array
    {
        return $this->tokens[$this->pos++];
    }

    /** Validasi cepat tanpa perlu nilai variabel sungguhan — dipakai form builder formula ASB. */
    public function extractVariableNames(string $expression): array
    {
        $names = [];
        foreach ($this->tokenize($expression) as $token) {
            if ($token['type'] === 'VAR') {
                $names[$token['value']] = true;
            }
        }

        return array_keys($names);
    }
}
