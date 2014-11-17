<?php

use mindplay\kisstpl\ViewService;

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

require __DIR__ . '/MockLayout.php';
require __DIR__ . '/MockViewModel.php';
require __DIR__ . '/MockNamespacedViewModel.php';

if (coverage()) {
    $filter = coverage()->filter();

    // whitelist the files to cover:

    $filter->addFileToWhitelist($root . '/src/ViewService.php');

    // begin code coverage:

    coverage()->start('test');
}

test(
    'Can render view',
    function () {
        $service = new ViewService(__DIR__);
        $view = new MockViewModel();

        ob_start();
        $service->render($view);
        $content = ob_get_clean();

        eq($content, MockViewModel::EXPECTED_VALUE, 'template content was rendered');

        unset($content);

        $content = $service->capture($view);

        eq($content, MockViewModel::EXPECTED_VALUE, 'template content was captured');
    }
);

test(
    'Can find view templates',
    function () {
        $ROOT_PATH = 'foo/bar';
        $TYPE = 'baz';

        $service = new ViewService($ROOT_PATH);

        $view = new \test\MockNamespacedViewModel();

        eq(
            invoke($service, 'findTemplate', array($view, $TYPE)),
            $ROOT_PATH . DIRECTORY_SEPARATOR
            . 'test/MockNamespacedViewModel'
            . '.' . $TYPE . '.php',
            'correctly resolves path to namespaced view-model'
        );

        $service = new ViewService($ROOT_PATH, 'test');

        eq(
            invoke($service, 'findTemplate', array($view, $TYPE)),
            $ROOT_PATH . DIRECTORY_SEPARATOR
            . 'MockNamespacedViewModel'
            . '.' . $TYPE . '.php',
            'correctly resolves path to namespaced view-model'
        );
    }
);

test(
    'Can capture content',
    function () {
        $EXPECTED_CONTENT = 'something_or_other';

        $service = new ViewService(__DIR__);
        $view = new MockViewModel();

        $service->begin($view->value);
        echo $EXPECTED_CONTENT;
        $service->end($view->value);

        eq($view->value, $EXPECTED_CONTENT, 'content was captured');
    }
);

test(
    'Throws expected Exceptions',
    function () {
        $view = new MockViewModel();

        $service = new ViewService(__DIR__);

        expect(
            'RuntimeException',
            'No matching call to begin()',
            function () use ($view, $service) {
                $service->render($view, 'missing_begin');
            }
        );

        $service = new ViewService(__DIR__);

        expect(
            'RuntimeException',
            'No matching call to end()',
            function () use ($view, $service) {
                $service->render($view, 'missing_end');
            }
        );

        $service = new ViewService(__DIR__);

        $foo = '';
        $bar = '';

        $service->begin($foo);
        $service->begin($bar);

        expect(
            'RuntimeException',
            'end() with mismatched begin()',
            function () use ($service) {
                $service->end($foo);
            }
        );

        $service = new ViewService(__DIR__, 'fudge');

        $model = new test\MockNamespacedViewModel();

        expect(
            'RuntimeException',
            'unsupported view-model',
            function () use ($service, $model) {
                invoke($service, 'findTemplate', array($model, 'view'));
            }
        );
    }
);

if (coverage()) {
    // end code coverage:

    coverage()->stop();

    // output code coverage report to console:

    $report = new PHP_CodeCoverage_Report_Text(10, 90, false, false);

    echo $report->process(coverage(), false);

    // output code coverage report for integration with CI tools:

    $report = new PHP_CodeCoverage_Report_Clover();

    $report->process(coverage(), $root . '/build/logs/clover.xml');
}

exit(status());

// https://gist.github.com/mindplay-dk/4260582

/**
 * @param string   $name     test description
 * @param callable $function test implementation
 */
function test($name, $function)
{
    echo "\n=== $name ===\n\n";

    try {
        call_user_func($function);
    } catch (Exception $e) {
        ok(false, "UNEXPECTED EXCEPTION", $e);
    }
}

/**
 * @param bool   $result result of assertion
 * @param string $why    description of assertion
 * @param mixed  $value  optional value (displays on failure)
 */
function ok($result, $why = null, $value = null)
{
    if ($result === true) {
        echo "- PASS: " . ($why === null ? 'OK' : $why) . ($value === null ? '' : ' (' . format($value) . ')') . "\n";
    } else {
        echo "# FAIL: " . ($why === null ? 'ERROR' : $why) . ($value === null ? '' : ' - ' . format($value, true)) . "\n";
        status(false);
    }
}

/**
 * @param mixed  $value    value
 * @param mixed  $expected expected value
 * @param string $why      description of assertion
 */
function eq($value, $expected, $why = null)
{
    $result = $value === $expected;

    $info = $result
        ? format($value)
        : "expected: " . format($expected, true) . ", got: " . format($value, true);

    ok($result, ($why === null ? $info : "$why ($info)"));
}

/**
 * @param string   $exception_type Exception type name
 * @param string   $why            description of assertion
 * @param callable $function       function expected to throw
 */
function expect($exception_type, $why, $function)
{
    try {
        call_user_func($function);
    } catch (Exception $e) {
        if ($e instanceof $exception_type) {
            ok(true, $why, $e);
            return;
        } else {
            $actual_type = get_class($e);
            ok(false, "$why (expected $exception_type but $actual_type was thrown)");
            return;
        }
    }

    ok(false, "$why (expected exception $exception_type was NOT thrown)");
}

/**
 * @param mixed $value
 * @param bool  $verbose
 *
 * @return string
 */
function format($value, $verbose = false)
{
    if ($value instanceof Exception) {
        return get_class($value) . ": \"" . $value->getMessage() . "\"";
    }

    if (! $verbose && is_array($value)) {
        return 'array[' . count($value) . ']';
    }

    if (is_bool($value)) {
        return $value ? 'TRUE' : 'FALSE';
    }

    if (is_object($value) && !$verbose) {
        return get_class($value);
    }

    return print_r($value, true);
}

/**
 * @param bool|null $status test status
 *
 * @return int number of failures
 */
function status($status = null)
{
    static $failures = 0;

    if ($status === false) {
        $failures += 1;
    }

    return $failures;
}

/**
 * @return PHP_CodeCoverage|null code coverage service, if available
 */
function coverage()
{
    static $coverage = null;

    if ($coverage === false) {
        return null; // code coverage unavailable
    }

    if ($coverage === null) {
        try {
            $coverage = new PHP_CodeCoverage;
        } catch (PHP_CodeCoverage_Exception $e) {
            echo "# Notice: no code coverage run-time available\n";
            $coverage = false;
            return null;
        }
    }

    return $coverage;
}

/**
 * Invoke a protected or private method (by means of reflection)
 *
 * @param object $object      the object on which to invoke a method
 * @param string $method_name the name of the method
 * @param array  $arguments   arguments to pass to the function
 *
 * @return mixed the return value from the function call
 */
function invoke($object, $method_name, $arguments = array())
{
    $class = new ReflectionClass(get_class($object));

    $method = $class->getMethod($method_name);

    $method->setAccessible(true);

    return $method->invokeArgs($object, $arguments);
}
