<?php

namespace mindplay\kisstpl;

use Closure;
use Exception;
use RuntimeException;
use Throwable;

/**
 * This service provides a view/template rendering service and a simple output capture facility.
 */
class ViewService implements Renderer
{
    /**
     * @var ViewFinder
     */
    public $finder;

    /**
     * @var string the default type of view
     *
     * @see render()
     */
    public $default_type = 'view';

    /**
     * @var string[] a stack of variable references being captured to
     *
     * @see begin()
     * @see end()
     */
    protected $capture_stack = array();

    /**
     * @var int a unique index to track use of the capture stack
     *
     * @see begin()
     * @see end()
     */
    private $capture_index = 0;

    /**
     * @var string[][] map where view-model class name => map where view type => view path
     */
    private $path_cache = array();

    /**
     * @var Closure[] map where view path => view Closure
     */
    private $closure_cache = array();

    /**
     * @param ViewFinder $finder
     */
    public function __construct(ViewFinder $finder)
    {
        $this->finder = $finder;
    }

    /**
     * Locate and render a PHP template for the given view, directly to output - as
     * opposed to {@see capture()} which will use output buffering to capture the
     * content and return it as a string.
     *
     * The view will be made available to the template as <code>$view</code> and the
     * calling context (<code>$this</code>) will be this ViewService.
     *
     * @param object      $view the view-model to render
     * @param string|null $type the type of view to render (optional)
     *
     * @return void
     *
     * @throws RuntimeException
     *
     * @see capture()
     */
    public function render($view, $type = null)
    {
        $__type = $type === null
            ? $this->default_type
            : $type;

        unset($type);

        $__class = get_class($view);

        if (! isset($this->path_cache[$__class][$__type])) {
            $this->path_cache[$__class][$__type] = $this->finder->findTemplate($view, $__type);
        }

        $__path = $this->path_cache[$__class][$__type];

        if ($__path === null) {
            $this->onMissingView($view, $__type);

            return;
        }

        $__depth = count($this->capture_stack);

        $ob_level = ob_get_level();

        $this->renderFile($__path, $view);

        if (ob_get_level() !== $ob_level) {
            $error = count($this->capture_stack) !== $__depth
                ? "begin() without matching end()"
                : "output buffer-level mismatch: was " . ob_get_level() . ", expected {$ob_level}";

            while (ob_get_level() > $ob_level) {
                ob_end_clean(); // clean up any hanging output buffers prior to throwing
            }

            throw new RuntimeException("{$error} in file: {$__path}");
        }
    }

    /**
     * Render and capture the output from a PHP template for the given view - as
     * opposed to {@see render()} which will render directly to output.
     *
     * Use capture only when necessary, such as when capturing content from a
     * rendered template to populate the body of an e-mail.
     *
     * @param object      $view the view-model to render
     * @param string|null $type the type of view to render (optional)
     *
     * @return string rendered content
     *
     * @see render()
     */
    public function capture($view, $type = null)
    {
        ob_start();

        try {
            $this->render($view, $type);
        } catch (Exception $exception) {
            // re-throwing below (PHP 5.3+)
        } catch (Throwable $exception) {
            // re-throwing below (PHP 7.0+)
        }

        $output = ob_get_clean();

        if (isset($exception)) {
            throw $exception;
        }

        return $output;
    }

    /**
     * @param string &$var target variable reference for captured content
     *
     * @return void
     *
     * @see end()
     */
    public function begin(&$var)
    {
        $index = $this->capture_index++;

        $var = __CLASS__ . "::\$capture_stack[{$index}]";

        if (in_array($var, $this->capture_stack, true)) {
            throw new RuntimeException("begin() with same reference as prior begin()");
        }

        $this->capture_stack[] = &$var;

        // begin buffering content to capture:
        ob_start();
    }

    /**
     * @param string &$var target variable reference for captured content
     *
     * @return void
     *
     * @throws RuntimeException
     *
     * @see begin()
     */
    public function end(&$var)
    {
        if (count($this->capture_stack) === 0) {
            throw new RuntimeException("end() without begin()");
        }

        $index = count($this->capture_stack) - 1;

        if ($this->capture_stack[$index] !== $var) {
            throw new RuntimeException("end() with mismatched begin()");
        }

        // capture the buffered content:
        $this->capture_stack[$index] = ob_get_clean();

        // remove target variable reference from stack:
        array_pop($this->capture_stack);
    }

    /**
     * Internally render a template file (or delegate to a cached closure)
     *
     * @param string $_path_ absolute path to PHP template
     * @param object $view the view-model to render
     *
     * @return void
     */
    protected function renderFile($_path_, $view)
    {
        if (!isset($this->closure_cache[$_path_])) {
            $_closure_ = require $_path_;

            if (is_callable($_closure_)) {
                $this->closure_cache[$_path_] = $_closure_;
            }
        }

        if (isset($this->closure_cache[$_path_])) {
            $this->renderClosure($this->closure_cache[$_path_], $view);
        }
    }

    /**
     * Internally render a template closure
     *
     * @param Closure $closure template closure
     * @param object  $view    the view-model to render
     *
     * @return void
     */
    protected function renderClosure($closure, $view)
    {
        call_user_func($closure, $view, $this);
    }

    /**
     * Called internally, if a view could not be resolved
     *
     * @param object      $view the view-model attempted to render
     * @param string|null $type the type of view attempted to render (optional)
     *
     * @return void
     *
     * @see render()
     */
    protected function onMissingView($view, $type)
    {
        $class = get_class($view);

        $paths = $this->finder->listSearchPaths($view, $type);

        $message = count($paths) > 0
            ? "searched paths:\n  * " . implode("\n  * ", $paths)
            : "no applicable path(s) found";

        throw new RuntimeException("no view of type \"{$type}\" found for model: {$class} - {$message}");
    }
}
