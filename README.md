mindplay/kisstpl
----------------

A very simple view-service / template-engine for plain PHP templates.

I wanted a template engine that uses view-models (objects) rather than
view-dictionaries (arrays) as are typical in most PHP template engines.

The view-service is tied to a root folder and a root namespace:

```PHP
$service = new ViewService('my/app/views', 'app\view');

$hello = new \app\view\HelloWorld();

$service->render($hello); // -> "my/app/views/HelloWorld.view.php"
```

The `render()` statement in this example will render the template
`my/app/views/HelloWorld.view.php`, passing the view-model object to
the rendered template as `$view`.

The `render()` method also accepts a second argument, allowing you to
render different templates for the same view-model:

```PHP
$service->render($hello, 'boom'); // -> "my/app/views/HelloWorld.boom.php"
$service->render($hello, 'bang'); // -> "my/app/views/HelloWorld.bang.php"
```

You can type-hint in the beginning of a template file for IDE support:

```PHP
<?php

use app\view\HelloWorld;

/**
 * @var HelloWorld $view
 */
```

Things like layouts can be accomplished by using plain OOP composition.
For example, let's say we have a `Layout` view-model with a `$body`
property, and we have an instance of the layout view-model in a `$layout`
property of the `HelloWorld` model - to implement a typical two-step
layout, in the HelloWorld template, use `begin()` and `end()` to buffer
and capture a section of content:

```PHP
<?php

use app\view\HelloWorld;
use mindplay\kisstpl\ViewService;

/**
 * @var HelloWorld $view
 * @var ViewService $this
 */

$view->layout->title = 'My Page!';

$this->begin($view->layout->body);

?>
<h1>Hello!</h1>
<p>Body content goes here...</p>
<?php

$this->end($view->layout->body);

$this->render($view->layout);
```

Note that `begin()` and `end()` take variable *references* as arguments -
the call to `end()` will apply the captured content to `$view->layout->body`.

There is deliberately no view rendering "pipeline", or any concept of
layout, and this is "a good thing" - your templates have complete control
of the rendering process, you have IDE support all the way,

You can also capture rendered content and return it, instead of sending
the rendered content to output:

```PHP
$content = $service->capture($hello);
```

You can use this feature to implement "partials", since it can be called
from within a template. Like `render()`, the `capture()` method also accepts
a second argument allowing you to render different views of the same view-model.

You can of course also extend `ViewService` with custom functionality - an
interface `Renderer` defines the four basic methods, `render()`, `capture()`,
`begin()` and `end()` so you can type-hint and swap out implementations as
needed.

You can also override the protected methods `getTemplateName()` and
`findTemplate()` if you need custom logic (such as "themes") specific to
your project.