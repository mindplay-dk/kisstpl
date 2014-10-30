<?php

namespace mindplay\kisstpl;

/**
 * @see ViewService
 */
interface Renderer
{
    /**
     * Locate and render a PHP template for the given view, directly to output - as
     * opposed to {@see capture()} which will use output buffering to capture the
     * content and return it as a string.
     *
     * The view will be made available to the template as <code>$view</code> and the
     * calling context (<code>$this</code>) will be this ViewService.
     *
     * @param object $view the view-model to render
     * @param string|null $type the type of view to render (optional)
     *
     * @return void
     *
     * @see capture()
     */
    public function render($view, $type = null);

    /**
     * Render and capture the output from a PHP template for the given view - as
     * opposed to {@see render()} which will render directly to output.
     *
     * Use capture only when necessary, such as when capturing content from a
     * rendered template to populate the body of an e-mail.
     *
     * @param object $view the view-model to render
     * @param string|null $type the type of view to render (optional)
     *
     * @return string rendered content
     *
     * @see render()
     */
    public function capture($view, $type = null);

    /**
     * @param string &$var target variable reference for captured content
     *
     * @see end()
     */
    public function begin(&$var);

    /**
     * @param string &$var target variable reference for captured content
     *
     * @see begin()
     */
    public function end(&$var);
}
