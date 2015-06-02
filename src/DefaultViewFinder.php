<?php

namespace mindplay\kisstpl;

/**
 * This view-finder maps a root namespace against a list of possible root paths,
 * defaulting to the first template that exists.
 */
class DefaultViewFinder implements ViewFinder
{
    /**
     * @var string|null base namespace for view-models supported by this finder
     */
    public $namespace;

    /**
     * @var MultiViewFinder
     */
    private $finders;

    /**
     * @param string[] $root_paths list of absolute paths to possible view root-folders (in order by priority)
     * @param string|null $namespace optional; base namespace for view-models supported by this finder
     */
    public function __construct(array $root_paths, $namespace = null)
    {
        $this->namespace = $namespace;

        $this->finders = new MultiViewFinder();

        foreach ($root_paths as $root_path) {
            $this->finders->addViewFinder(new SimpleViewFinder($root_path, $namespace));
        }
    }

    /**
     * @inheritdoc
     */
    public function findTemplate($view_model, $type)
    {
        return $this->finders->findTemplate($view_model, $type);
    }

    /**
     * @inheritdoc
     */
    public function listSearchPaths($view_model, $type)
    {
        return $this->finders->listSearchPaths($view_model, $type);
    }
}
