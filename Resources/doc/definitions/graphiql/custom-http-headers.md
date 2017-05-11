Custom HTTP headers
==============

GraphiQL, provided by this bundle, sends the following default headers on each request:

```js
headers = {
  "Accept": "application/json",
  "Content-Type": "application/json"
};
```

Headers sent by GraphiQL can be modified. 
For example, let's assume an `access-token` header is required in development.
The header can be added the following way:

1. Override the default GraphiQL template:

```yml
# app/config/config_dev.yml
overblog_graphql:
  templates:
    graphiql: AppBundle:GraphiQL:index.html.twig
```
2. Create a new template:  

```twig
{# src/AppBundle/Resources/views/GraphiQL/index.html.twig #}
{% extends 'OverblogGraphQLBundle:GraphiQL:index.html.twig' %}

{% block graphql_fetcher_headers %}
headers = {
  "Accept": "application/json",
  "Content-Type": "application/json",
  "access-token": "sometoken"
};
{% endblock graphql_fetcher_headers %}
```

Or append headers instead of replacing the default one:

```twig
{# src/AppBundle/Resources/views/GraphiQL/index.html.twig #}
{% extends 'OverblogGraphQLBundle:GraphiQL:index.html.twig' %}

{% block graphql_fetcher_headers %}
{{ parent() }}
headers["access-token"] = "sometoken";
{% endblock graphql_fetcher_headers %}
```
