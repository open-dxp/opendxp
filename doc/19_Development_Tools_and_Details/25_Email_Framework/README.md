# Email Framework

## General Information
The OpenDXP Email Framework provides an easy way to send/create emails with OpenDXP.
  
For this you have several components:
* Document\\Email
* [OpenDxp\Mail](./01_OpenDxp_Mail.md)

OpenDXP provides a `OpenDxp\Mail` Class which extends the `\Symfony\Component\Mime\Email` Class. 
If email settings are configured in your `config/config.yaml` then on initializing 
`OpenDxp\Mail` object, these settings applied automatically

It is recommended to configure email settings in `config/config.yaml` file:
```yaml
opendxp:
    email:
        sender:
            name: 'OpenDXP Demo'
            email: contact@opendxp.io
        return:
            name: ''
            email: ''
```
and debug email addresses should be configured in Admin *Settings* > *System* > *Debug* > *Debug Email Addresses*.

If the Debug Mode is enabled, all emails will be sent to the
Debug Email recipients defined in *Settings* > *System* > *Debug* > *Debug Email Addresses*.
Additionally the debug information (to whom the email would have been sent) is appended to the email
and the Subject contains the prefix "Debug email:".

This is done by extending Symfony Mailer, with injected service `RedirectingPlugin`, which calls beforeSendPerformed before mail is sent and sendPerformed immediately after email is sent.

Emails are sent via transport and `\OpenDxp\Mailer` requires transports: `main` for sending emails and  `opendxp_newsletter` for sending newsletters(if newsletter specific settings are used and OpenDXPNewsletterBundle is enabled and installed), which needs to be configured in your config.yaml e.g.,
```yaml
framework:
    mailer:
        transports:
            main: smtp://user:pass@smtp.example.com:port
            opendxp_newsletter: smtp://user:pass@smtp.example.com:port
```
Please refer to the [Transport Setup](https://symfony.com/doc/current/mailer.html#transport-setup) for further details on how this can be set up.


OpenDXP provides a `Document Email` type where you can define the recipients ... (more information 
[here](../../03_Documents/README.md)) and Twig variables. 

To send a email you just create a `Email Document` in the OpenDXP Backend UI, define the subject, 
recipients, add Dynamic Placeholders... and pass this document to the `OpenDxp\Mail` object. All 
nasty stuff (creating valid URLs, embedding CSS, compile Less files, rendering the document..) is 
automatically handled by the `OpenDxp\Mail` object.

In the `Settings` section of the `Email Document` you can use `Full Username <user@domain.fr>` or `Full Username (user@domain.fr)` to set full username.

## Usage Example
Lets assume that we have created a `Email Document` in the OpenDXP Backen UI (`/email/myemaildocument`) 
which looks like this:

To send this document as email we just have to write the following code-snippet in our controller 
action:

```php
//dynamic parameters
$params = array('firstName' => 'Pim',
                'lastName' => 'Core',
                'product' => \OpenDxp\Model\DataObject::getById(73613)
                );
 
//sending the email
$mail = new \OpenDxp\Mail();
$mail->to('example@opendxp.io');
$mail->setDocument('/email/myemaildocument');
$mail->setParams($params);
$mail->send();
```

you can access the parameters in your mail content.
```twig
Hello {{ firstName }} {{ lastName }}
Regarding the product {{ product.getName() }} ....
```

#### Sending a Plain Text Email:
```php
$mail = new \OpenDxp\Mail();
$mail->to('example@opendxp.io');
$mail->text("This is just plain text");
$mail->send();
```

#### Sending a Rich Text (HTML) Email: 
```php
$mail = new \OpenDxp\Mail();
$mail->to('example@opendxp.io');
$mail->bcc("bcc@opendxp.io");
$mail->html("<b>some</b> rich text");
$mail->send();
```

## Sandbox Restrictions
Sending mails renders user controlled twig templates in a sandbox with a restrictive security policy.
The policy allows the tag `set`, the filters `escape`, `trans` and `default` and the functions `path` and `asset`.
Every function whose name starts with `opendxp_` is allowed too, except `opendxp_dump`.

A template reads objects and does not change them. It may read an object of a readable class through its getters,
its `is` and `has` methods, `__toString()` and its properties. Elements, the values of their fields, quantity value
units, asset thumbnails and dates are readable. A date may also call `format()`. Every other method is refused.
So `{{ object.name }}`, `{{ object.getName() }}` and `{{ object.date.format("Y") }}` work, but
`{{ object.save() }}` and `{{ container.get("database_connection") }}` do not.

A placeholder sees the parameters of the mail and the Twig globals.
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

The subject is rendered as plain text. The body is rendered as HTML, so a parameter is escaped there.
