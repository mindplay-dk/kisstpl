<?php

namespace mindplay\kisstpl;

/**
 * This defines the interface for locating the template for a given view-model.
 */
interface ViewFinder
{
    /**
     * @param object $view_model view-model
     * @param string $type       the type of view
     *
     * @return string absolute path to PHP template
     */
    public function findTemplate($view_model, $type);
}
