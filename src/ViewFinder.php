<?php

namespace mindplay\kisstpl;

/**
 * This defines the interface for view-finders, responsible for establishing
 * search path(s) for templates for a given view-model.
 *
 * @see ViewService::render()
 */
interface ViewFinder
{
    /**
     * Establish the template path for a given view-model and view-type.
     *
     * May return a path to a file that does not exist.
     *
     * Should return NULL, if unable to establish any template path.
     *
     * @param object $view_model view-model
     * @param string $type       the type of view
     *
     * @return string|null absolute path to PHP template (or NULL, if unresolved)
     */
    public function findTemplate($view_model, $type);

    /**
     * Establish all possible template paths for a given view-model and view-type.
     *
     * May include paths to files that do not exist.
     *
     * Should return an empty array, if unable to establish any template path(s).
     *
     * @param object $view_model view-model
     * @param string $type       the type of view
     *                           
     * @return string[] list of absolute paths to PHP templates
     */
    public function listSearchPaths($view_model, $type);
}
