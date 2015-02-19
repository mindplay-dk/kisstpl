<?php

use mindplay\kisstpl\ViewService;

require __DIR__ . '/header.php';

if (coverage()) {
    $filter = coverage()->filter();

    // whitelist the files to cover:

    $filter->addFileToWhitelist($root . '/src/ViewService.php');

    // begin code coverage:

    coverage()->start('test');
}

test(
    'Can render views',
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

        unset($content);

        $content = $service->capture($view, 'closure');

        eq($content, MockViewModel::EXPECTED_VALUE, 'template content from a closure was captured');
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
