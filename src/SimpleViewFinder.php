<?php

namespace mindplay\kisstpl;

use RuntimeException;

/**
 * Simple view-finder implementation - maps a namespace directly to a root path
 * and assumes that the template exists.
 */
class SimpleViewFinder implements ViewFinder
{
    /**
     * @var string absolute path to the view root-folder for the base namespace
     */
    public $root_path;

    /**
     * @var string|null base namespace for view-models supported by this finder
     */
    public $namespace = null;

    /**
     * @param string      $root_path absolute path to view root-folder
     * @param string|null $namespace optional; base namespace for view-models supported by this finder
     */
    public function __construct($root_path, $namespace = null)
    {
        $this->root_path = rtrim($root_path, DIRECTORY_SEPARATOR);
        $this->namespace = $namespace;
    }

    /**
     * {@inheritdoc}
     *
     * @see ViewFinder::findTemplate()
     */
    public function findTemplate($view_model, $type)
    {
        $name = $this->getTemplateName($view_model);

        return $name
            ? $this->root_path . DIRECTORY_SEPARATOR . $name . ".{$type}.php"
            : null;
    }

    /**
     * {@inheritdoc}
     *
     * @see ViewFinder::listSearchPaths()
     */
    public function listSearchPaths($view_model, $type)
    {
        return array_filter(array($this->findTemplate($view_model, $type)));
    }

    /**
     * @param object $view_model view-model
     *
     * @return string|null template name (e.g. "Bar/Baz" for class Foo\Bar\Baz if $namespace is 'Foo')
     *
     * @throws RuntimeException if the given view-model doesn't belong to the base namespace
     *
     * @see ViewFinder::findTemplate()
     */
    protected function getTemplateName($view_model)
    {
        $name = get_class($view_model);

        if ($this->namespace !== null) {
            $prefix = $this->namespace . '\\';
            $length = strlen($prefix);

            if (strncmp($name, $prefix, $length) !== 0) {
                return null;
            }

            $name = substr($name, $length); // trim namespace prefix from class-name
        }

        return strtr($name, '\\', '/');
    }
}
