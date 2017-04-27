# Silverstripe links
Adds a Link Object that can be link to a URL, Email, Phone number, an internal Page or File.

## Installation
Composer is the recommended way of installing SilverStripe modules.
```
composer require silverstripe-modular-project/silverstripe-links
```

## Requirements

- silverstripe/framework 3.5.\*
- unclecheese/display-logic 1.4.\*

## Maintainers

- [Gorrie Coe](https://github.com/gorriecoe)

## Conflict
This module has a direct conflict with [Linkable](https://github.com/sheadawson/silverstripe-linkable).

### Example usage

```php
class Page extends SiteTree
{
    private static $has_one = array(
        'ExampleLink' => 'Link'
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab(
            'Root.Link',
            LinkField::create(
                'ExampleLinkID',
                'Link to page or file'
            )
        );

        return $fields;
    }
}
```

In your template, you can render the links anchor tag with

```html
{$ExampleLink}
```

### Template options

Basic usage

```html
<!-- has one relationship -->
{$ExampleLink}

<!-- many relationship -->
<% loop ExampleLinks %>
    {$Me}
<% end_loop %>
```

Define link classes

```html
<!-- has one relationship -->
{$ExampleLink.setCSSClass(button)}

<!-- many relationship -->
<% loop ExampleLinks %>
    {$setCSSClass(button)}
<% end_loop %>
```

Define a custom template to render the link

```html
<!-- has one relationship -->
{$ExampleLink.renderWith(Link_button)}

<!-- many relationship -->
<% loop ExampleLinks %>
    {$renderWith(Link_button)}
<% end_loop %>
```

Define a custom style.  This will apply a css class and render a custom template if it exists.  The example below will look for Link_button.ss in the includes directory.

```html
<!-- has one relationship -->
{$ExampleLink.setStyle(button)}

<!-- many relationship -->
<% loop ExampleLinks %>
    {$setStyle(button)}
<% end_loop %>
```

Custom template

```html
<% with ExampleLink %>
    <% if LinkURL %>
        <a href="{$LinkURL}"{$TargetAttr}{$ClassAttr}>
            {$Title}
        </a>
    <% end_if %>
<% end_with %>
```

### CMS Selectable Style

You can offer CMS users the ability to select from a list of styles, allowing them to choose how their Link should be rendered. To enable this feature, register them in your site config.yml file as below.

```yaml
Link:
  styles:
    button: Description of button template # applies button class and looks for Link_button.ss template
    iconbutton: Description of iconbutton template # applies iconbutton class and looks for Link_iconbutton.ss template
```

### Limit allowed Link types

Globally limit link types.  To limit types define them in your site config.yml file as below.

```yaml
Link:
  allowed_types:
    - URL
    - SiteTree
```

To limit link types locally for each field.  It is important to note that defining local types does not inherit from global but instead overrides it.

```php
LinkField::create(
    'ExampleLinkID',
    'Link Title'
)
->setAllowedTypes('URL','Phone')
```
