<?php

use mindplay\kisstpl\DefaultViewFinder;
use mindplay\kisstpl\LocalViewFinder;
use mindplay\kisstpl\MultiViewFinder;
use mindplay\kisstpl\SimpleViewFinder;
use mindplay\kisstpl\ViewService;

require __DIR__ . '/header.php';

test(
    'Can render views',
    function () {
        $service = new ViewService(new SimpleViewFinder(__DIR__));
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
    'Can cache template paths',
    function () {
        $finder = new MockViewFinder(__DIR__ . '/MockViewModel.view.php');

        $service = new ViewService($finder);

        $view = new MockViewModel();

        $service->capture($view);

        eq($finder->called, 1);

        $service->capture($view);

        eq($finder->called, 1, 'findTemplate() gets called only the first time');
    }
);

test(
    'Can find view templates (SimpleViewFinder)',
    function () {
        $ROOT_PATH = 'foo/bar';
        $TYPE = 'baz';

        $finder = new SimpleViewFinder($ROOT_PATH);

        $view = new \test\MockNamespacedViewModel();

        eq(
            $finder->findTemplate($view, $TYPE),
            $ROOT_PATH . DIRECTORY_SEPARATOR
            . 'test/MockNamespacedViewModel'
            . '.' . $TYPE . '.php',
            'correctly resolves path to namespaced view-model'
        );

        $finder = new SimpleViewFinder($ROOT_PATH, 'test');

        eq(
            $finder->findTemplate($view, $TYPE),
            $ROOT_PATH . DIRECTORY_SEPARATOR
            . 'MockNamespacedViewModel'
            . '.' . $TYPE . '.php',
            'correctly resolves path to namespaced view-model'
        );

        $finder = new SimpleViewFinder(__DIR__, 'fudge');

        ok($finder->findTemplate($view, 'view') === null, 'resolves as NULL for unsupported namespace');
        eq($finder->listSearchPaths($view, 'view'), array(), 'returns an empty list of search paths');
    }
);

test(
    'can find view-templates (DefaultViewFinder)',
    function () {
        $path_lists = array(
            array(__DIR__),
            array('fudge', __DIR__),
            array(__DIR__, 'fudge'),
        );

        $view = new MockViewModel();

        foreach ($path_lists as $path_list) {
            $finder = new DefaultViewFinder($path_list);

            $path = file_exists($finder->findTemplate($view, 'view'));

            ok($path, 'finds template from paths (' . implode(', ', $path_list) . ')', $path);

            eq($finder->findTemplate($view, 'fudge'), null, 'returns NULL if unresolved');
        }
    }
);

test(
    'can find local view-template',
    function () {
        $finder = new LocalViewFinder();

        $model = new MockViewModel();

        $expected_path = __DIR__ . DIRECTORY_SEPARATOR . 'MockViewModel.view.php';
        eq($finder->listSearchPaths($model, 'view'), array($expected_path), 'should list expected path');

        $path = $finder->findTemplate($model, 'view');
        eq($path, $expected_path, 'should find local view-template');

        $multi = new MultiViewFinder();
        $multi->addViewFinder($finder);
        $service = new ViewService($multi);

        eq($multi->listSearchPaths($model, 'view'), array($expected_path));

        expect(
            'RuntimeException',
            'should throw on missing template',
            function () use ($service) {
                // this will fail because no local view-file exists for this view-model:
                $service->capture(new \test\MockNamespacedViewModel());
            }
        );

        $multi->addViewFinder(new MockViewFinder("foo"));

        // same conditions as before, because adding the view-finder should cause it to have lower priority:

        eq($finder->listSearchPaths($model, 'view'), array($expected_path), 'should still list expected path');
        eq($path, $expected_path, 'should still find local view-template');

        $multi->pushViewFinder(new MockViewFinder("bar"));

        eq(
            $multi->listSearchPaths($model, 'view'),
            array(
                "bar", // least priority (pushed last)
                $expected_path,
                "foo", // least priority (added last)
            ),
            'view-finders provide search paths with expected priority'
        );

        eq($path, $expected_path, 'pushed view-finder determines the result');
    }
);

test(
    'Can capture content',
    function () {
        $EXPECTED_CONTENT = 'something_or_other';

        $service = new ViewService(new SimpleViewFinder(__DIR__));

        $var = null;

        $service->begin($var);
        echo $EXPECTED_CONTENT;
        $service->end($var);

        eq($var, $EXPECTED_CONTENT, 'content was captured');
    }
);

test(
    'Throws expected Exceptions',
    function () {
        $view = new MockViewModel();

        $service = new ViewService(new DefaultviewFinder(array('fudge')));

        expect(
            'RuntimeException',
            'Template not found',
            function () use ($view, $service) {
                $service->render($view);
            }
        );

        $service = new ViewService(new SimpleViewFinder(__DIR__));

        expect(
            'RuntimeException',
            'No matching call to begin()',
            function () use ($view, $service) {
                $service->render($view, 'missing_begin');
            }
        );

        $service = new ViewService(new SimpleViewFinder(__DIR__));

        $ob_level = ob_get_level();

        expect(
            'RuntimeException',
            'No matching call to end()',
            function () use ($view, $service) {
                $service->render($view, 'missing_end');
            },
            "/" . preg_quote("begin() without matching end() in file") . "/"
        );

        eq(ob_get_level(), $ob_level, "should clean up hanging output buffers");

        $ob_level = ob_get_level();

        expect(
            'RuntimeException',
            'No matching call to end()',
            function () use ($view, $service) {
                $service->render($view, 'ob_start');
            },
            "/output buffer-level mismatch/"
        );

        eq(ob_get_level(), $ob_level, "should clean up hanging output buffers");

        $service = new ViewService(new SimpleViewFinder(__DIR__));

        $foo = null;
        $bar = null;

        $service->begin($foo);
        $service->begin($bar);

        expect(
            'RuntimeException',
            'end() with mismatched begin()',
            function () use ($service, &$foo) {
                $service->end($foo);
            }
        );

        $service->end($bar);
        $service->end($foo);

        $service->begin($foo);

        expect(
            'RuntimeException',
            'begin() with same reference as prior begin()',
            function () use ($service, &$foo) {
                $service->begin($foo);
            }
        );
    }
);

test(
    'internally calls onMissingView()',
    function () {
        $ROOT_PATH = '/foo/bar';
        $VIEW_TYPE = "baz";

        $finder = new SimpleViewFinder($ROOT_PATH, 'test');

        $view = new MockViewModel();

        eq($finder->findTemplate($view, $VIEW_TYPE), null, "precondition: view-finder can NOT find a template");

        $service = new MockViewService($finder);

        $service->capture($view, $VIEW_TYPE);

        eq($service->missed_view, $view);
        eq($service->missed_type, $VIEW_TYPE);
    }
);

configure()->enableCodeCoverage(dirname(__DIR__) . '/build/logs/clover.xml', $root . '/src');

exit(run());
