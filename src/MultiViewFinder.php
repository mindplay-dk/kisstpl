<?php

namespace mindplay\kisstpl;

/**
 * This view-finder aggregates a stack of view-finders and attempts to
 * locate views by trying every added view-finder in order - use this
 * for advanced (modular) "theme" scenarios, etc.
 */
class MultiViewFinder implements ViewFinder
{
    /**
     * @var ViewFinder[] stack of view-finders
     */
    protected $finders = array();

    /**
     * Add a view-finder to the top of the stack - the added view-finder will
     * have the highest priority of view-finders currently on the stack.
     *
     * @param ViewFinder $finder
     */
    public function pushViewFinder(ViewFinder $finder)
    {
        array_unshift($this->finders, $finder);
    }

    /**
     * Add a view-finder to the bottom of the stack - the added view-finder will
     * have the lowest priority of view-finders currently on the stack.
     *
     * @param ViewFinder $finder
     */
    public function addViewFinder(ViewFinder $finder)
    {
        $this->finders[] = $finder;
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
            $paths = array_merge($paths, $finder->listSearchPaths($view_model, $type));
        }

        return $paths;
    }
}
