<?php

namespace mindplay\kisstpl;

use ReflectionClass;

/**
 * This view-finder assumes a local view-file located "locally", in the
 * same folder as the class-file, e.g.:
 *
 *     src/view/HomePage.php         # class file
 *     src/view/HomePage.view.php    # matching template (for default type "view")
 */
class LocalViewFinder implements ViewFinder
{
    /**
     * {@inheritdoc}
     *
     * @see ViewFinder::findTemplate()
     */
    public function findTemplate($view_model, $type)
    {
        $class = new ReflectionClass($view_model);

        $path = dirname($class->getFileName());

        $name = $class->getShortName();

        return $path . DIRECTORY_SEPARATOR . $name . ".{$type}.php";
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
}
