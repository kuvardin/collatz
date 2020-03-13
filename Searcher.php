<?php

/**
 * Class Searcher
 * @author Maxim Kuvardin <maxim@kuvard.in>
 */
class Searcher
{
    /**
     * @var array Последовательность
     */
    private $m = [];

    /**
     * @var int Максимальное значение элементов последовательности
     */
    private $m_max = 0;

    /**
     * @var int Длина последовательности
     */
    private $k = 0;

    /**
     * @var array Буфер степеней
     */
    private $pows_of_number = [];

    /**
     * @var array Буфер знаменателей
     */
    private $denomenators = [];

    /**
     * @var int|string Количество всех последовательностей
     */
    private $sequences_all = 0;

    /**
     * @var int Минимально допустимая сумма элементов последовательности
     */
    private $sequence_sum_min = 0;

    /**
     * @var GMP[][]
     */
    private $muls = [];

    /**
     * @var resource[]
     */
    private $denomerators = [];

    /**
     * Searcher constructor.
     * @param int $m_max
     * @param int $k
     */
    public function __construct(int $m_max, int $k)
    {
        $this->m_max = $m_max;
        $this->k = $k;

        // Буферизация степеней 2 и 3
        $this->countPowsOfNumber(2, $this->m_max * $this->k)
            ->countPowsOfNumber(3, $this->k);

        // Количество всех последовательностей
        $this->sequences_all = gmp_strval(gmp_pow($this->m_max, $this->k));

        // Начальная последовательность
        for ($i = 0; $i < $this->k; $i++) {
            $this->m[$i] = 1;
        }

        // Вычисление минимально допустимой суммы элементов последовательности
        $this->sequence_sum_min = $this->countSequenceSumMin();

        // Буферизация значений знаменателя
        for ($i = 0; $i <= $this->m_max * $this->k; $i++) {
            $this->denomerators[$i] = gmp_sub($this->pows_of_number[2][$i], $this->pows_of_number[3][$this->k]);
        }
    }

    /**
     * @param int $number
     * @param int $max_pow
     * @return Searcher
     */
    private function countPowsOfNumber(int $number, int $max_pow): self
    {
        $this->pows_of_number[$number] = [
            0 => gmp_init('1'),
        ];

        for ($i = 1; $i <= $max_pow; $i++) {
            $this->pows_of_number[$number][$i] = gmp_mul($this->pows_of_number[$number][$i - 1], $number);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function nextSequence(): bool
    {
        $i = 0;

        // В каждой новой последовательности увеличение идет на единицу
        $buff = 1;

        do {
            $sum = $this->m[$i] + $buff - 1;
            $buff = (int)($sum / $this->m_max);
            $this->m[$i] = $sum % $this->m_max + 1;
            $i++;
        } while ($buff !== 0);

        // Отбрасываем повторяющуюся в циклах часть
        return $this->m[$this->k - 1] !== $this->m_max;
    }

    /**
     * @return int
     */
    public function getSequencesNumber(): int
    {
        return $this->sequences_all;
    }

    /**
     * @return bool
     */
    public function searchMMax(): bool
    {
        return in_array($this->m_max, $this->m, true);
    }

    /**
     * @return string
     */
    public function getSequenceString(): string
    {
        return implode(' ', $this->m);
    }

    /**
     * @return bool
     */
    public function checkSequenceSum(): bool
    {
        return array_sum($this->m) >= $this->sequence_sum_min;
    }

    /**
     * @return int
     */
    public function getSequenceSumMin(): int
    {
        return $this->sequence_sum_min;
    }

    /**
     * @return int
     */
    public function countSequenceSumMin()
    {
        $result = 0;
        for ($i = 0; $i <= $this->m_max * $this->k; $i++) {
            if (gmp_cmp($this->pows_of_number[2][$i], $this->pows_of_number[3][$this->k]) > 0) {
                $result = $i;
                break;
            }
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function checkUniqueness(): bool
    {
        for ($q = 1; $q < $this->k; $q++) {
            for ($e = $this->k - 1; $e >= 0; $e--) {
                $m_index = ($e + $q) % $this->k;
                if ($this->m[$e] !== $this->m[$m_index]) {
                    if ($this->m[$e] > $this->m[$m_index]) {
                        return false;
                    }
                    break;
                }
            }
        }
        return true;
    }

    /**
     * @return bool
     */
    public function isNoCycle(): bool
    {
        // Максимальная длина возможных подциклов - максимальная длина сдвига $e
        $e_max = (int)($this->k / 2);

        for ($e = 1; $e <= $e_max; $e++) {
            // Длина возможных подциклов = [1..$e_max]
            if ($this->k % $e === 0) { // $e должна быть делителем $k
                $j = 0; // Количество совпавших подряд элементов в $m и "$m со сдвигом в $e"
                // Сверяем элементы в $m и "$m со сдвигом в $e"
                while ($j < $this->k && $this->m[$j] === $this->m[($j + $e) % $this->k]) {
                    $j++;
                }

                if ($j === $this->k) {
                    return false;
                }
                // При совпадении всех элементов отбрасываем последовательность
            }
        }
        return true;
    }

    /**
     * @return string|null
     */
    public function getResult(): ?string
    {
        $numerator = $this->pows_of_number[3][$this->k];
        $pow_of_two = 0;

        for ($i = 1; $i < $this->k; $i++) {
            $pow_of_two += $this->m[$i];
            $numerator = gmp_add($numerator, $this->mul($pow_of_two, $this->k - $i));
        }

        // Пропускаем непрошедшие проверку
        if (gmp_strval(gmp_div_r($numerator, $this->denomerators[array_sum($this->m)])) !== '0') {
            return null;
        }

        return gmp_strval(gmp_add(gmp_div_q($numerator, $this->denomerators[array_sum($this->m)]), gmp_init('1')));
    }

    /**
     * @param int $pow_of_two
     * @param int $pow_of_three
     * @return GMP
     */
    public function mul(int $pow_of_two, int $pow_of_three): GMP
    {
        if (!isset($this->muls[$pow_of_two][$pow_of_three])) {
            $this->muls[$pow_of_two][$pow_of_three] = gmp_mul($this->pows_of_number[3][$pow_of_three], $this->pows_of_number[2][$pow_of_two]);
        }

        return $this->muls[$pow_of_two][$pow_of_three];
    }
}
