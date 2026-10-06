# Dynamic Text Labels

Similar to the [CalculatedValue](../../../05_Objects/01_Object_Classes/01_Data_Types/10_Calculated_Value_Type.md) data type,
it is possible to generate the Layout Text dynamically based on the current object and the label's context. There are two options for defining dynamic content: 
- Providing a custom renderer class
- Using twig in template
This is an alternative to the static text defined in the class definition.

For all ways, the preview tab shows a preview of the generated content. If you are using object context in your content, then just drag & drop object on "Drag Object for Preview" field before checking the output in preview tab.


## Custom Renderer Class

Let's consider the following example.

It states that we want to use a custom renderer service which implements `DynamicTextLabelInterface` and in turn returns dynamic text string from `renderLayoutText` method. We also want to pass some additional data (*some additional data :)* in this example) to the rendering method.

![Class Definition](../../../img/dynamic_textlabel_1.png)

Here is an example for a rendering class.

```php
<?php

namespace App\Helpers;

use OpenDxp\DateFormat;
use OpenDxp\Model\DataObject\Concrete;

class CustomRenderer implements DynamicTextLabelInterface
{
    /**
     * @param string $data as provided in the class definition
     */
    public function renderLayoutText(string $data, ?Concrete $object, array $params): string
    {
        $text = '<h1 style="color: #F00;">Last reload: ' . date(DateFormat::ISO_8601) . '</h1>' .
            '<h2>Additional Data: ' . $data . '</h2>';

        if ($object) {
            $text .= '<h3>BTW, my fullpath is: ' . $object->getFullPath() . ' and my ID is ' . $object->getId() . '</h3>';
        }

        return $text;
    }
}
```

*$data* will contain the additional data from the class definition. In *$params* you will find additional information about the current context.
For example: If the text label lives inside a field collection, *$params* will contain the name of the field collection (and of course the name of the label itself).

The result will be as follows:

![Editmode](../../../img/dynamic_textlabel_2.png)

## Twig & Preview
It is possible to use Twig syntax inside htmleditor (Source Edit) and Renderer class. You can also check the generated output in preview tab.

Following variables are available in twig context: 
- `object` - current data object
- `data` - data provided to renderer defined in the class defintion

Here is an example of Twig content in htmleditor source edit mode:

![Template Class Definition](../../../img/dynamic_textlabel_3.png)

![Template editmode](../../../img/dynamic_textlabel_4.png)

### Sandbox Restrictions
Dynamic Text renders user controlled twig templates in a sandbox with a restrictive security policy.
The policy allows the tag `set`, the filters `escape`, `trans` and `default` and the functions `path` and `asset`.
Every function whose name starts with `opendxp_` is allowed too, except `opendxp_dump`.

A template reads objects and does not change them. It may read an object of a readable class through its getters,
its `is` and `has` methods, `__toString()` and its properties. Elements, the values of their fields, quantity value
units, asset thumbnails and dates are readable. A date may also call `format()`. Every other method is refused.
So `{{ object.name }}`, `{{ object.getName() }}` and `{{ object.date.format("Y") }}` work, but
`{{ object.save() }}` and `{{ container.get("database_connection") }}` do not.

The text sees the object, the field name, the layout and the Twig globals.
The globals `app` and `container` are objects of classes that are not readable.

Please use the following configuration to allow more in template rendering:

```yaml
opendxp:
    templating_engine:
        twig:
            sandbox_security_policy:
                tags: ['if']
                filters: ['upper']
                functions: ['include', 'path']
                methods:
                    App\Model\Price: ['format']
                readable_classes:
                    - App\Model\Cart
```

`methods` allows single methods of a class. `readable_classes` makes every object of a class readable.
The configuration adds to the defaults and never replaces them.
