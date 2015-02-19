<?php

namespace mindplay\kisstpl;

use RuntimeException;

/**
 * Default view-finder implementation
 */
class NamespaceViewFinder implements ViewFinder
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
     * @param string      $root_path absolute path to view root-folder
     * @param string|null $namespace optional; base namespace for view-models supported by this factory
     */
    public function __construct($root_path, $namespace = null)
    {
        $this->root_path = rtrim($root_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->namespace = $namespace;
    }

    /**
     * {@inheritdoc}
     *
     * @see ViewFinder::findTemplate()
     */
    public function findTemplate($view_model, $type)
    {
        return $this->root_path . $this->getTemplateName($view_model) . ".{$type}.php";
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
}
