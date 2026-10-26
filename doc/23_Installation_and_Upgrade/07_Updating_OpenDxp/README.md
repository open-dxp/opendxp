# Updating OpenDXP

## Our Backward Compatibility Promise
Since we're building on top of Symfony and in an app, OpenDXP and Symfony code get mixed together, it makes sense that we're adopting the same backward compatibility promise for PHP code. 

For further information on how you can ensure that your application won't break when upgrading to a newer version of the same major release branch, please have a look at
https://symfony.com/doc/current/contributing/code/bc.html

- Carefully read the [changelog](https://github.com/open-dxp/opendxp/blob/1.x/CHANGELOG.md) before any update. 
- Check your version constraint for `open-dxp/opendxp` in your `composer.json` and adapt it if necessary to match with the desired target version.
- Run `COMPOSER_MEMORY_LIMIT=-1 composer update`
- Clear the data cache `bin/console opendxp:cache:clear`
- Run core migrations: `bin/console doctrine:migrations:migrate --prefix=OpenDxp\\Bundle\\CoreBundle`
- If your project uses the Generic Execution Engine, run its migrations: `bin/console doctrine:migrations:migrate --prefix=OpenDxp\\Bundle\\GenericExecutionEngineBundle`
- (optional) Run [migrations of your app or bundles](../../19_Development_Tools_and_Details/37_Migrations.md)
