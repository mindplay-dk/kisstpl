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
     * @var string absolute path to the view root-folder for the base namespace
     */
    public $root_path;

    /**
     * @var string|null base namespace for view-models supported by this service
     */
    public $namespace = null;

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
     * @param string      $root_path absolute path to view root-folder
     * @param string|null $namespace optional; base namespace for view-models supported by this factory
     */
    public function __construct($root_path, $namespace = null)
    {
        $this->root_path = rtrim($root_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->namespace = $namespace;
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

        $__path = $this->findTemplate($view, $__type);

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
     * @param object $view_model view-model
     *
     * @return string template name (e.g. "Bar/Baz" for class Foo\Bar\Baz if $namespace is 'Foo')
     *
     * @throws RuntimeException if the given view-model doesn't belong to the base namespace
     */
    protected function getTemplateName($view_model)
    {
        $name = get_class($view_model);

        if ($this->namespace !== null) {
            $prefix = $this->namespace . '\\';
            $length = strlen($prefix);

            if (strncmp($name, $prefix, $length) !== 0) {
                throw new RuntimeException("unsupported view-model: {$name} - expected namespace: {$this->namespace}");
            }

            $name = substr($name, $length); // trim namespace prefix from class-name
        }

        return strtr($name, '\\', '/');
    }

    /**
     * @param object $view_model view-model
     * @param string $type       the type of view
     *
     * @return string absolute path to PHP template
     */
    protected function findTemplate($view_model, $type)
    {
        return $this->root_path . $this->getTemplateName($view_model) . ".{$type}.php";
    }
}
