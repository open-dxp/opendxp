# Logging

There are several different kinds of logs in OpenDXP. All of them are located under `/var/log` and get rotated
as well as compressed automatically on every day (7 days retention) by the maintenance command. 
 
## `<env>.log`
This is definitely one of the most important logs and also the default logging location. 

OpenDXP uses Symfony default monolog logging with following channels: `opendxp`, `opendxp_api`, `session`. 
For details see [Symfonys monolog docs](https://symfony.com/doc/current/logging.html).

### Context of a log entry

Every log entry carries its context in the field `extra` at the end of the line:

| Key        | Content                                                    |
|------------|------------------------------------------------------------|
| `token`    | The user of the request and the roles of the user          |
| `requests` | The controller, the route and the parameters of the route  |
| `command`  | The name and the arguments of the console command          |

An error in the admin interface:

```text
[2026-10-14T15:17:46+02:00] request.ERROR: Uncaught PHP Exception ...DocumentNotFoundException: "Document with id
99999999 not found" at GetDocumentDataHandler.php line 50 {"exception":"..."} {"token":{"authenticated":true,
"roles":["ROLE_OPENDXP_ADMIN"],"user_identifier":"admin"},"requests":[{"controller":"...DocumentController::
getDataByIdAction","route":"opendxp_admin_document_document_getdatabyid","route_params":[]}]}
```

An entry of a console command:

```text
[2026-10-14T15:18:04+02:00] app.DEBUG: Successfully released the "opendxp:thumbnails:image" lock. {...}
{"token":null,"command":{"name":"opendxp:thumbnails:image","arguments":{"command":"opendxp:thumbnails:image"}}}
```

The IP address of a request is personal data, so OpenDXP leaves it out. A project adds it together with the URL and
the method of the request with the `WebProcessor` of Symfony:

```yaml
services:
    Symfony\Bridge\Monolog\Processor\WebProcessor:
        tags: [monolog.processor]
```

## php.log
By default OpenDXP writes PHP-Engine Log Messages to the file `php.log`.
You can change this by add the following to your symfony config:
```yaml
monolog:
    handlers:
        error:
            type: stream
            path: "%kernel.logs_dir%/own_php.log"
            level: error
```

## usagelog.log
In this log you can find every action done within the OpenDXP Backend Interface. It can be deactivated by configuring `disable_usage_statistics` in `config/config.yaml`:

```yaml
opendxp:
    general:
        disable_usage_statistics: true
```

##### Example Entry: 
``` 
2021-04-26T13:18:35+0200 : 2|OpenDxp\Bundle\AdminBundle\Controller\Admin\Document\PageController::saveAction|opendxp_admin_document_page_save|{"task":"publish","id":"1","data":"{\"cImage_0\":{\"data\":{\"id\":337,\"path\":\"\\\/..."}
2021-04-26T13:18:35+0200 : 2|OpenDxp\Bundle\AdminBundle\Controller\Admin\Asset\AssetController::getImageThumbnailAction|opendxp_admin_asset_getimagethumbnail|{"id":"3","alt":"","height":"undefined","thumbnail":"portalCarousel","opendxp_editmode":"1"}
```

##### Explanation

| Value (from the example above)                                                    | Description                               |
|-----------------------------------------------------------------------------------|-------------------------------------------|
| `2021-04-26T13:18:35+0200`                                                        | Timestamp                                 |
| `2`                                                                               | User-ID                                   |
| `OpenDxp\Bundle\AdminBundle\Controller\Admin\Document\PageController::saveAction` | Module\Controller::Action                 |
| `opendxp_admin_document_page_save`                                                | Route name                                |
| `\{"task":"pub .... \}`                                                           | Request Parameters (shortened & censored) |

## redirect.log
Sometimes it's necessary to debug redirects, for example when a redirect ends in an infinite loop. 
In this log you can see every request where a redirect takes action. 

##### Example
```
2021-04-26T14:03:20+0200 : 10.242.2.255          Custom-Redirect ID: 1, Source: /asdsad/redirectsource/asd -> /en/Events
```

> [!NOTE]
> Redirects are logged into a `redirect` monolog log channel at info level. By default, OpenDXP logs that channel into `var/log/redirect.log`.
> Of course, the corresponding monolog handler configuration can be adapted as needed.

## Writing Your Own Log Files
To create a custom log entry, we need to create the monolog log channels and log handlers configuration. Here is an example on how to log in a custom filename called `custom.log`

```yaml
monolog:
    handlers:
        custom_handler:
            level:    debug
            type:     stream
            path:     '%kernel.logs_dir%/custom.log'
            channels: [custom_log]
    channels: [custom_log, some_other_channel]

```
It is possible to inject the `Psr\Log\LoggerInterface` by changing the variable name eg. `$customLogLogger` (camel case channel name + `Logger`) and Symfony will automatically wire the specified channel.

```php
class SomeService {
    public function __construct(\Psr\Log\LoggerInterface $customLogLogger)
    {
        $customLogLogger->debug('Test Message');
    }
}
```

For more, please refer to [Monolog Documentation](https://symfony.com/doc/6.2/logging/channels_handlers.html)
