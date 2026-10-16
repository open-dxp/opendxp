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

A data type can split its value into several columns of the object grid. The classification store has a column for
each key. The store values of the ecommerce bundle have a column for each store and value.

The key of such a column has the form `~<field type>~<field name>~<grid column>`. The data type alone decides what
the last part means. The classification store uses `<group id>-<key id>`, for example `1-2`.

The PHP class implements `OpenDxp\Model\DataObject\ClassDefinition\Data\GridColumnsInterface`. Each grid column has
a data definition of its own, for example a `Numeric` or a `Select`. The grid shows, edits, filters and exports the
column through this definition, in the same way as an ordinary field.

| Method                           | Purpose                                                                |
|:---------------------------------|:-----------------------------------------------------------------------|
| `hasGridColumn()`                | Keeps a saved grid column only while the field still knows it.         |
| `getGridColumnDefinition()`      | Returns the definition of the column. Its title is the column label.   |
| `getGridColumnName()`            | Returns the name of the column in the header of a CSV export.          |
| `getGridColumnValue()`           | Returns the value of an object in the format of the definition.        |
| `setGridColumnValue()`           | Sets the value of an object in the format of the definition.           |
| `getGridColumnForCsvExport()`    | Returns the text of the column in a CSV export.                        |
| `addGridColumnToQuery()`         | Selects the value of the column under an alias.                        |
| `getGridColumnFilterCondition()` | Returns the condition of a filter on this alias.                       |

The grid sorts by the alias. A filter condition becomes part of the `HAVING` clause.

An object without a value of its own shows the value of its first parent with a value. The grid marks the cell as
inherited.

The JavaScript tag of the field offers a dialog. The grid configuration opens it when the field is dropped into the
selected columns:

```js
createGridColumnConfigDialog: function () {
    return new app.myDataType.columnConfigDialog();
},
```

The dialog has the method `getConfigDialog(ownerTree, node, selectionPanel)`. It sets `key`, `text`, `layout` and
`dataType` of the dropped node, or removes the node. The layout is the column definition, and `dataType` is its field
type.

> [!NOTE]
> A filter on such a column only finds values the object has of its own. An inherited value has no row of the object.
