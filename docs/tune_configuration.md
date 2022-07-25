Tune configuration
==================

Custom GraphQl configuration parsers
------------------------------------

You can configure custom GraphQl configuration parsers.
Your parsers MUST implement at least `\Overblog\GraphQLBundle\Config\Parser\ParserInterface`
and optionally `\Overblog\GraphQLBundle\Config\Parser\PreParserInterface` when required.

Default values will be applied when omitted.

```yaml
overblog_graphql:
    # ...
    parsers:
        yaml: 'Overblog\GraphQLBundle\Config\Parser\YamlParser'
        graphql: 'Overblog\GraphQLBundle\Config\Parser\GraphQLParser'
        annotation: 'Overblog\GraphQLBundle\Config\Parser\AnnotationParser'
        attribute: 'Overblog\GraphQLBundle\Config\Parser\AttributeParser'
    # ...
```
