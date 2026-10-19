# Adding Object Datatypes
With plugins, it is also possible to add individual data types for OpenDXP Objects. 
Following steps are necessary to do so: 

1) Create a PHP class for server-side implementation:
 This class needs to extend `OpenDxp\Model\DataObject\ClassDefinition\Data` and defines how your data type is stored into 
  database, how the getters and setters for OpenDXP objects are generated and how data is sent to and read from 
  OpenDXP Admin UI. 
   
   For examples have a look at the OpenDXP core datatypes at 
   [github](https://github.com/open-dxp/opendxp/tree/11.x/models/DataObject/ClassDefinition/Data). 

2) Create JavaScript class for Class Definition editor (object data): 
This JavaScript class defines the representation of the data type in the *Class Definition editor* and therefore where
it is allowed (object, objectbricks, fieldcollections, localizedfields), its group, its label, its icon and its config
options in class editor. 

   It needs to extend `opendxp.object.classes.data.data`, be located in namespace `opendxp.object.classes.data` and named after the 
   `$fieldtype` property of the corresponding PHP class.
     
   For examples have a look at the OpenDXP core datatypes at 
   [github](https://github.com/open-dxp/admin-bundle/tree/1.x/public/js/opendxp/object/classes/data)


3) Create JavaScript class for object editor (object tag):
This JavaScript class defines the representation of the data type in the *object editor* and therefore defines how data
is presented and an can be entered within OpenDXP objects. 

   It needs to extend `opendxp.object.tags.abstract`, be located in namespace `opendxp.object.tags` and named after the 
   `$fieldtype` property of the corresponding PHP class.
     
   For examples have a look at the OpenDXP core datatypes at
   [github](https://github.com/open-dxp/admin-bundle/tree/1.x/public/js/opendxp/object/tags)
   
   
4) Register a datatype in OpenDXP
Register a datatype in OpenDXP by extending the `opendxp.objects.class_definitions.data.map` configuration. 
This can be done in any config file which is loaded (e.g. `config/config.yaml`), but if you provide the datatype 
with a bundle you should define it in a configuration file which is [automatically loaded](./03_Auto_Loading_Config_And_Routing_Definitions.md). 

   Example:

```yaml
# config/config.yaml

opendxp:
    objects:
        class_definitions:
            data:
                map:
                  myDataType: \App\Model\DataObject\Data\MyDataType
```

## Grid Columns

A field usually fills exactly one column of the object grid. That is a poor fit for a field that holds several
values at once. A product dimension holds a width, a height and a depth. A classification store holds hundreds of keys.
The store values of the ecommerce bundle hold a price and a tax rule for every store.

With `GridColumnsInterface` such a field offers each of its values as a column of its own. An editor drops the field
into the grid configuration, picks the value in a small dialog and gets a full column for it. The column behaves like
any ordinary field in the grid:

- The cell shows the value and marks it when the object inherits it from a parent.
- The cell editor and the batch edit change exactly this value and leave the rest of the field alone.
- The filter, the sorting and the CSV export work on it.

The data type does not build any of this itself. It describes each column with an existing data definition, for
example a `Numeric`, a `Select` or a `QuantityValue`. The grid then uses the renderer, the editor, the filter and the
conversions of that definition. The classification store works in exactly this way, so every key type it supports is
available as a column.

### The Key of a Column

The grid saves a column under a key of the form `~<field type>~<field name>~<grid column>`. Only the last part belongs
to the data type, and the data type alone decides what it means. The classification store uses
`<group id>-<key id>`, so `~classificationstore~specs~3-12` is key 12 in group 3 of the field `specs`. The store
values use `<store id>-<type>`, so `~ecommerceStoreValues~storeValues~1-price` is the price in store 1.

`Service::findGridColumn()` reads such a key and returns a `FieldGridColumn`. It checks the field type, the field name
and the column, and it returns `null` for any key the field does not know. The grid works only with this object and
never takes a key apart itself.

### Implement the Interface

The following data type stores the dimensions of a product in the query columns `<name>__width`, `<name>__height`
and `<name>__depth`. It offers each dimension as a numeric column:

```php
use Doctrine\DBAL\Query\QueryBuilder;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\ClassDefinition\Data\GridColumnsInterface;
use OpenDxp\Model\DataObject\Concrete;

final class Dimensions extends Data implements GridColumnsInterface
{
    private const array GRID_COLUMNS = ['width', 'height', 'depth'];

    public function hasGridColumn(string $gridColumn): bool
    {
        return in_array($gridColumn, self::GRID_COLUMNS, true);
    }

    public function getGridColumnDefinition(string $gridColumn): ?Data
    {
        $definition = new Data\Numeric();
        $definition->setName($this->getName());
        $definition->setTitle(sprintf('%s: %s', $this->getTitle(), ucfirst($gridColumn)));

        return $definition;
    }

    public function getGridColumnName(string $gridColumn): string
    {
        return $gridColumn;
    }

    public function getGridColumnValue(string $gridColumn, Concrete $object, array $params = []): mixed
    {
        return $object->get($this->getName())?->{'get' . ucfirst($gridColumn)}();
    }

    public function setGridColumnValue(string $gridColumn, Concrete $object, mixed $value, array $params = []): void
    {
        $dimensions = $object->get($this->getName()) ?? new DimensionsValue();
        $dimensions->{'set' . ucfirst($gridColumn)}($value);

        $object->setValue($this->getName(), $dimensions);
    }

    public function getGridColumnForCsvExport(string $gridColumn, Concrete $object, array $params = []): string
    {
        return (string) $this->getGridColumnValue($gridColumn, $object, $params);
    }

    public function addGridColumnToQuery(
        QueryBuilder $queryBuilder,
        ClassDefinition $class,
        string $objectTable,
        string $gridColumn,
        string $alias,
        array $params = [],
    ): void {
        $queryBuilder->addSelect(sprintf('%s.%s__%s AS %s', $objectTable, $this->getName(), $gridColumn, $alias));
    }

    public function getGridColumnFilterCondition(
        string $gridColumn,
        mixed $value,
        string $operator,
        string $alias,
    ): string {
        return $this->getGridColumnDefinition($gridColumn)->getFilterConditionExt(
            $value,
            $operator,
            ['name' => $alias],
        );
    }
}
```

Values pass between the grid and the field in the format of the column definition. For a `Numeric` that is a number.
For a `Select` it is the value of the option. `getGridColumnValue()` reads the value through the getter of the object.
The grid reads it once with inherited values and once without them. That is how it finds the parent behind an
inherited value and marks the cell.

`addGridColumnToQuery()` selects the value under the alias that the grid hands over. The grid sorts by this alias,
and every filter condition ends up in the `HAVING` clause of the listing. A field with its values in another table
joins that table here. The classification store and the store values both do that.

### Choose the Column in a Dialog

The JavaScript tag of the field returns a dialog. The grid configuration opens it as soon as an editor drops the field
into the selected columns:

```js
createGridColumnConfigDialog: function () {
    return new app.dimensions.columnConfigDialog();
},
```

The dialog receives the dropped node and a callback with `getConfigDialog(ownerTree, node, selectionPanel, callback)`.
It sets `key`, `text`, `layout` and `dataType` of the node, and then it calls the callback. The callback refreshes the
preview of the grid configuration. The layout is the column definition, and `dataType` is its field type. An editor
can drop the field as often as needed and gets another column every time.

> [!NOTE]
> A filter on such a column finds only values the object holds itself. An inherited value has no row of the object,
> so the database cannot match it.
