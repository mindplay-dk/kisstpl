<?php

namespace mindplay\kisstpl;

use RuntimeException;

/**
 * This view-finder maps a root namespace against a list of possible root paths,
 * defaulting to the first template that exists - use this if you need to override
 * templates in modules or implement "themes", etc.
 */
class DefaultViewFinder implements ViewFinder
{
    /**
     * @var SimpleViewFinder[]
     */
    private $finders = array();

    /**
     * @param string[] $root_paths list of absolute paths to possible view root-folders (in order by priority)
     * @param string|null $namespace optional; base namespace for view-models supported by this finder
     */
    public function __construct(array $root_paths, $namespace = null)
    {
        foreach ($root_paths as $root_path) {
            $this->finders[] = new SimpleViewFinder($root_path, $namespace);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see ViewFinder::findTemplate()
     */
    public function findTemplate($view_model, $type)
    {
        foreach ($this->finders as $finder) {
            $path = $finder->findTemplate($view_model, $type);

            if ($path !== null && file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @see ViewFinder::listSearchPaths()
     */
    public function listSearchPaths($view_model, $type)
    {
        $paths = array();

        foreach ($this->finders as $finder) {
            $paths[] = $finder->findTemplate($view_model, $type);
        }

        return array_filter($paths);
    }
}
