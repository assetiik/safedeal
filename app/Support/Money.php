<?php

namespace App\Support;

final class Money
{
    public static function tenge(int|string|null $amount): string
    {
        return number_format((int) $amount, 0, ',', ' ').' ₸';
    }

    public static function signed(int $amount, bool $incoming): string
    {
        $sign = $incoming ? '+' : '−';

        return $sign.number_format(abs($amount), 0, ',', ' ').' ₸';
    }

    public static function tengeWords(int $amount): string
    {
        $amount = abs($amount);

        if ($amount === 0) {
            return 'Ноль тенге';
        }

        $units = ['', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'];
        $unitsFem = ['', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'];
        $teens = ['десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать'];
        $tens = ['', '', 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто'];
        $hundreds = ['', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот'];

        $triplet = function (int $n, bool $feminine) use ($units, $unitsFem, $teens, $tens, $hundreds): string {
            $parts = [];
            $h = intdiv($n, 100);
            $n %= 100;
            if ($h > 0) {
                $parts[] = $hundreds[$h];
            }
            if ($n >= 10 && $n <= 19) {
                $parts[] = $teens[$n - 10];
            } else {
                $t = intdiv($n, 10);
                $u = $n % 10;
                if ($t > 0) {
                    $parts[] = $tens[$t];
                }
                if ($u > 0) {
                    $parts[] = $feminine ? $unitsFem[$u] : $units[$u];
                }
            }

            return implode(' ', array_filter($parts));
        };

        $form = function (int $n, array $forms): string {
            $n = abs($n) % 100;
            $n1 = $n % 10;
            if ($n > 10 && $n < 20) {
                return $forms[2];
            }
            if ($n1 > 1 && $n1 < 5) {
                return $forms[1];
            }
            if ($n1 === 1) {
                return $forms[0];
            }

            return $forms[2];
        };

        $millions = intdiv($amount, 1_000_000);
        $thousands = intdiv($amount % 1_000_000, 1000);
        $rest = $amount % 1000;
        $parts = [];

        if ($millions > 0) {
            $parts[] = trim($triplet($millions, false).' '.$form($millions, ['миллион', 'миллиона', 'миллионов']));
        }
        if ($thousands > 0) {
            $parts[] = trim($triplet($thousands, true).' '.$form($thousands, ['тысяча', 'тысячи', 'тысяч']));
        }
        if ($rest > 0 || $parts === []) {
            $parts[] = $triplet($rest, false);
        }

        $words = trim(implode(' ', array_filter($parts))).' тенге';

        return mb_strtoupper(mb_substr($words, 0, 1)).mb_substr($words, 1);
    }
}
