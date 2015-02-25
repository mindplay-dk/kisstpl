<?php

namespace mindplay\kisstpl;

use Closure;
use RuntimeException;

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
     * @var Closure[] map where view-model class name => cached view closure
     */
    private $cache = array();

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

        $__path = $this->finder->findTemplate($view, $__type);

        if ($__path === null) {
            $this->onMissingView($view, $__type); return;
        }

        $__depth = count($this->capture_stack);

        $__class = get_class($view);

        if (isset($this->cache[$__class][$__type])) {
            // invoke closure cached during previous call:

            call_user_func($this->cache[$__class][$__type], $view, $this);
        } else {
            $__closure = require $__path;

            if (is_callable($__closure)) {
                // inject closure into cache and invoke:

                $this->cache[$__class][$__type] = $__closure;

                call_user_func($__closure, $view, $this);
            }
        }

        if (count($this->capture_stack) !== $__depth) {
            throw new RuntimeException('begin() without matching end() in file: ' . $__path);
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

        $this->render($view, $type);

        return ob_get_clean();
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
