Lists
======

To denote that a field uses a List type the item type is wrapped in square brackets like this: **[Pet]**.
If using yaml like config file, the double quote is required like this: **"[Pet]"**.  
If using annotation, you just need to use the GraphQLToMany annotation, or the Doctrine ORM annotation XToMany.  
  
```php
<?php

/**
 * Episode
 */
class Saison
{
    /**
     * @\Overblog\GraphQLBundle\Annotation\GraphQLToMany(target="Episode")
     */
    public $episodes;
}
```
