<?php

use mindplay\benchpress\Benchmark;
use mindplay\kisstpl\SimpleViewFinder;
use mindplay\kisstpl\ViewService;

require __DIR__ . '/header.php';

echo "Benchmark file vs closure rendering time...\n\n";

$bench = new Benchmark(500);

$view = new MockViewModel();

foreach (array(1,2,3,5,10) as $iterations) {

    $service = new ViewService(new SimpleViewFinder(__DIR__));

    $file_time = $bench->mark(function () use ($view, $service, $iterations) {
        for ($i=1; $i<=$iterations; $i++) {
            $content = $service->capture($view);
        }
    });

    echo "File rendering time x {$iterations} =    " . number_format($file_time, 3) . "\n";

    $service = new ViewService(new SimpleViewFinder(__DIR__));

    $closure_time = $bench->mark(function () use ($view, $service, $iterations) {
        for ($i=1; $i<=$iterations; $i++) {
            $content = $service->capture($view, 'closure');
        }
    });

    echo "Closure rendering time x {$iterations} = " . number_format($closure_time, 3) . "\n";

    $times = number_format($file_time / $closure_time, 2);

    echo "Rendering via a closure {$iterations} times is {$times} times faster than rendering a flat file\n\n";
}
