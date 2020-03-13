<?php

$start_time = microtime();

$k = isset($argv[1]) ? (int)$argv[1] : 0;
if ($k <= 0) {
	exit("Неверное значение k\nКоманда: php search.php %k% %m_max%\n");
}


$m_max = isset($argv[2]) ? (int)$argv[2] : 0;
if ($m_max <= 0) {
	exit("Неверное значение m_max\nКоманда: php search.php %k% %m_max%\n");
}

// Подключение класса Searcher
require __DIR__ . '/Searcher.php';

// Длина шага подведения итогов
$step_length = 1000000;

$ces = new Searcher($m_max, $k);

$sequences_valid = 0;
$sequences_success = 0;

echo "|m| = $k\n";
echo "m[i] ∈ [1..$m_max]\n";
echo "min(∑m[i]) = {$ces->getSequenceSumMin()}\n";
echo "$m_max^$k = {$ces->getSequencesNumber()}\n";

$step_this = 0;
$seq_checked = gmp_init('0');
$time_step = microtime();
$times_steps = [];
$times_steps_i = 0;
$times_steps_length = 10;

do { // Перебор всех возможных последовательностей
	// Вывод информации каждые $step_length последовательностей
	if (++$step_this === $step_length) {

		// Проверено последовательностей
		$seq_checked = gmp_add($seq_checked, $step_length);

		// Осталось последовательностей
		$seq_left = gmp_add($ces->getSequencesNumber(), gmp_neg($seq_checked));

		// Осталось шагов
		$steps_left = gmp_div_q($seq_left, $step_length);

		// Общее время
		$time_all = gen_time($start_time, 2);

		// Время на последний шаг
		$time_step = gen_time($time_step, 2);

		// Среднее время шага
		$times_steps[$times_steps_i] = $time_step;
		if (++$times_steps_i === $times_steps_length) {
			$times_steps_i = 0;
		}

		$time_step_average = array_sum($times_steps) / $times_steps_length;

		// Оставшееся время
		$time_left = gmp_div_q(gmp_mul($steps_left, (string) round($time_step_average * 10000)), 10000);

		printf(
			"%10s/%s | %s/%s | %.2f/%.2f\r",
			gmp_strval($seq_checked),
			$ces->getSequencesNumber(),
			parse_time($time_all),
			parse_time(gmp_intval($time_left)),
			$time_step,
			round($time_step_average, 2)
		);

		$step_this = 0;
		$time_step = microtime();
	}

	// Последовательность должна содержать элемент со значением
	// равным максимальному значению ($m_max)
	if (!$ces->searchMMax()) {
		continue;
	}

	// Сумма последовательности должна быть выше минимальной
	if (!$ces->checkSequenceSum()) {
		continue;
	}

	// Берем только одну последовательность для данного цикла
	// (123, 231, 312 - одни и те же циклические последовательности)
	if (!$ces->checkUniqueness()) {
		continue;
	}

	// Отсеиваем последовательности, содержащие в себе циклы
	// (123123123 - циклическая последовательность 123)
	if (!$ces->isNoCycle()) {
		continue;
	}

	// Считаем данную последовательность валидной
	$sequences_valid++;

	if ($ces->getResult() === null) {
		continue;
	}

	// Считаем данную последовательность подходящей
	$sequences_success++;

	echo "{$ces->getResult()} | {$ces->getSequenceString()}\n";
} while ($ces->nextSequence());

$time_all = gen_time($start_time, 6);

echo "\n";
echo "Valids: $sequences_valid\n";
echo "Successes: $sequences_success\n";
$time_all_string = parse_time($time_all);
echo "Time: {$time_all_string}\n";
echo "----------------------------------\n\n";


/**
 * @param $start_time
 * @param null $round
 * @return float
 */
function gen_time($start_time, $round = null) {
	$start_array = explode(' ', $start_time);
	$end_array = explode(' ', microtime());
	$time = $end_array[1] + $end_array[0] - $start_array[1] - $start_array[0];
	return $round === null ? $time : round($time, $round);
}

/**
 * @param int $time
 * @return string
 */
function parse_time(int $time): string {
	return round(floor($time / 86400)) . ':' . date('H:i:s', round($time));
}
