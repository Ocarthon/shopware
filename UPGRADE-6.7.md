# 6.7.0.0 Upgrade Guide
**NOTE:** All the breaking changes described here can be already opted in by activating the `v6.7.0.0` [feature flag](https://developer.shopware.com/docs/resources/references/adr/2022-01-20-feature-flags-for-major-versions.html#activating-the-flag) on previous versions.

# Notable Changes

# Webpack to vite migration for the administration
We are switching the build system for our administration from webpack to vite. 
This means that when your plugins depends on a custom `webpack.config.js` file, you'll need to migrate it to a `vite.config.js` file.
**More information about how to upgrade will be available soon.**

Additionally, this means that you will need to distribute a separate plugin version starting for 6.7, when you extend the administration to distribute the correct build files.
For more information please take a look at the [docs](https://developer.shopware.com/docs/guides/plugins/plugins/administration/system-updates/vite.html).

**Note:** This change can be activated separately with the `ADMIN_VITE` feature flag.

# Vue.js Enhancements (full native vue 3 support)
## Removal of Vue 2 compatibility layer
The Vue 2 compatibility layer has been removed from the administration. This means that all components that still rely on Vue 2 features need to be updated.
This ensures that our administration stays future-proof and we can make use of the most recent Vue 3 features.

For detailed explanation of what was covered by the compatibility layer and what needs to be updated, please refer to the [Vue docs](https://v3-migration.vuejs.org/migration-build.html).

**Note:** This change can be activated separately with the `DISABLE_VUE_COMPAT` feature flag.

## Migration from Vuex to Pinia
For Vue 3 the default state management library has become Pinia, therefore we are migrating from Vuex to Pinia. to stay as close to the default as possible.
When you use default stores in your plugin you need to switch from `Shopware.State` (Vuex) to `Shopware.Store` (Pinia).
Adding your own Vuex stores is still possible, however it is recommended that you switch to Pinia as well.

Here is an example of how to switch from Vuex to Pinia:
```ts
// Old Vuex implementation
Shopware.State.registerModule('example', {
    state: {
        id: '',
    },
    getters: {
        idStart(state) {
            return state.id.substring(0, 4);
        }
    },
    mutations: {
        setId(state, id) {
            state.id = id;
        }
    },
    actions: {
        async asyncFoo({ commit }, id) {
            // Do some async stuff
            return Promise.resolve(() => {
                commit('setId', id);
                
                return id;
            });
        }
    }
});

// New Pinia implementation
// Notice that the mutation setId was removed! You can directly modify a Pinia store state after retrieving it with Shopware.Store.get.
Shopware.Store.register({
    id: 'example',
    state: () => ({
        id: '',
    }),
    getters: {
        idStart: () => this.id.substring(0, 4),
    },
    actions: {
        async asyncFoo(id) {
            // Do some async stuff
            return Promise.resolve(() => {
                this.id = id;

                return id;
            });
        }
    }
});
```

For more information refer to the [docs](https://developer.shopware.com/docs/resources/references/adr/2024-06-17-replace-vuex-with-pinia.html#replace-vuex-with-pinia).

# Cache Rework
**Note:** Those changes can be activated separately with the `cache_rework` feature flag.

## Delayed Cache Invalidation
The cache invalidation will be delayed by default. This means that the cache will be invalidated in regular intervals and not immediately.
This will lead to better cache hit rates and way less (duplicated) cache invalidations, which will improve efficiency and scalability of the system.
As this feature is now active by default the previous `shopware.cache.invalidation.delay` configuration is removed.

The default interval is 5 min, this can be changed by adjusting the run interval of the `shopware.invalidate_cache` scheduled task.

If you sent an API request with critical information, where the cache should be invalidated immediately, you can set the `sw-force-cache-invalidate` header on your request.
```
POST /api/product
sw-force-cache-invalidate: 1
```

To manually clear all the stale caches you can either run the `cache:clear:delayed` command or use the `/api/_action/cache-delayed` API endpoint.
```
bin/console cache:clear:delayed
```
```
DELETE /api/_action/cache-delayed
```

For debugging there is the `cache:watch:delayed` command available, to watch the cache tags that are stored in the delayed cache invalidation queue.
```
bin/console cache:watch:delayed
```

## Removal of Store-API route caching
The Store-API route caching has been removed. This means that the `Cached*Route` classes will be removed.
This solves some weird states when the HTTP-Cache was invalidated separately from the route cache.
Additionally, the cache hit rate for the Store-API was low, so the performance impact should be minimal, but the amount of cache items and cache invalidations will be reduced.
This overall should lead to more effective cache resource usage.

## Introduction of ESI for header and footer
The header and footer are now loaded via ESI.
This allows to cache the header and footer separately from the rest of the page.
Two new routes `\header` and `\footer` were added to receive the rendered header and footer.
The rendered header and footer are included into the page with the Twig function `render_esi`, which calls the previously mentioned routes.
Two new templates `src/Storefront/Resources/views/storefront/layout/header.html.twig` and `src/Storefront/Resources/views/storefront/layout/footer.html.twig` were introduced as new entry points for the header and footer.
Make sure to adjust your template extensions to be compatible with the new structure.
The block names are still the same, so it just should be necessary to extend from the new templates.

# Major Library Updates
We upgraded the following libraries to their latest versions:
* [DBAL 4.x](https://github.com/doctrine/dbal/blob/4.2.x/UPGRADE.md#upgrade-to-40): When you are using DBAL directly, please check the upgrade guide.
* [PHPUnit 11.x](https://github.com/sebastianbergmann/phpunit/blob/11.0.0/ChangeLog-11.0.md#1100---2024-02-02): You need to adjust your tests to the new PHPUnit version.
* [Dompdf 3.x](https://github.com/dompdf/dompdf/releases/tag/v3.0.0): Please check your document templates, if they are still rendered as expected.
* [oauth2-server 9.x](https://oauth2.thephpleague.com/upgrade-guide/): We don't expect you are affected by this change on the code level, however the library does not support some requests that are not spec-compliant, look at the detailed [upgrade guide](#non-spec-compliant-apioauthtoken-requests-are-not-supported-anymore).

# Accessibility Compliance
In alignment with the European Accessibility Act (EAA) we made significant accessibility improvements.
**Note:** Those changes can be activated separately with the `ACCESSIBILITY_TWEAKS` feature flag.

<details>
  <summary>Detailed Changes</summary>

## Storefront product box accessibility: Removed duplicate links around the product image in product cards
**Affected template: `Resources/views/storefront/component/product/card/box-standard.html.twig`**

The anchor link around the product image `a.product-image-link` is removed and replaced with the link of the product name `a.product-name` that now uses the `stretched-link` helper class:
```diff
<div class="card product-box box-standard">
    <div class="card-body">
        <div class="product-image-wrapper">
-            <a href="https://shopware.local/Example-Product/SW-01931a101dcc725aa3affc0ff408ee31">
                <img src="https://shopware.local/media/a3/22/75/1731309077/Example-Product_%283%29.webp?ts=1731309077" alt="Example-Product">
-            </a>
        </div>

        <div class="product-info">
            <a href="https://shopware.local/Example-Product/SW-01931a101dcc725aa3affc0ff408ee31"
+               class="product-name stretched-link"> {# <------ stretched-link is used instead #}
                Example-Product
            </a>
        </div>
    </div>
</div>
```

## Storefront base font-size
In regard to better readability the base font-size of the storefront is updated to the browser standard of `1rem` (16px). Other text formatting is adjusted accordingly. The following variables and properties are changed:

* `$font-size-base` changed from `0.875rem` to `1rem`.
* `$font-size-lg` changed from `1rem` to `1.125rem`.
* `$font-size-sm` changed from `0.75rem` to `0.875rem`.
* `$paragraph-margin-bottom` changed from `1rem` to `2rem`.
* `line-height` of `.quantity-selector-group-input` changed to `1rem`.
* `font-size` of `.form-text` changed from `0.875rem` to `$font-size-base`.
* `font-size` of `.account-profile-change` changed from `$font-size-sm` to `$font-size-base`.
* `font-size` of `.product-description` changed from `0.875rem` to `$font-size-base`.
* `line-height` of `.product-description` changed from `1.125rem` to `$line-height-base`.
* `height` of `.product-description` changed from `3.375rem` to `4.5rem`.
* `line-height` of `.quantity-selector-group-input` changed to `1rem`.
* `font-size` of `.main-navigation-menu` changed from `$font-size-lg` to `$font-size-base`.
* `font-size` of `.navigation-flyout-category-link`changed from `$font-size-lg` to `$font-size-base`.

## Change Storefront language and currency dropdown items to buttons
The `.top-bar-list-item` elements inside the "top-bar" dropdown menus will contain `<button>` elements instead of a hidden `<input type="radio">` elements.

**Affected templates:**
* `Resources/views/storefront/layout/header/actions/language-widget.html.twig`
* `Resources/views/storefront/layout/header/actions/currency-widget.html.twig`

### Before:
```html
<ul class="top-bar-list dropdown-menu dropdown-menu-end">
    <li class="top-bar-list-item">
        <label class="top-bar-list-label" for="top-bar-01918f15b7e2711083e85ec52ac29411">
            <input class="top-bar-list-radio" id="top-bar-01918f15b7e2711083e85ec52ac29411" value="01918f15b7e2711083e85ec52ac29411" name="currencyId" type="radio">
            £ GBP
        </label>
    </li>
    <li class="top-bar-list-item">
        <label class="top-bar-list-label" for="top-bar-01918f15b7e2711083e85ec52ac29411">
            <input class="top-bar-list-radio" id="top-bar-01918f15b7e2711083e85ec52ac29411" value="01918f15b7e2711083e85ec52ac29411" name="currencyId" type="radio">
            $ USD
        </label>
    </li>
</ul>
```

### After:
```html
<ul class="top-bar-list dropdown-menu dropdown-menu-end">
    <li class="top-bar-list-item">
        <button class="dropdown-item" type="submit" name="currencyId" id="top-bar-01918f15b7e2711083e85ec52ac29411" value="01918f15b7e2711083e85ec52ac29411">
            <span aria-hidden="true" class="top-bar-list-item-currency-symbol">£</span>
            Pound
        </button>
    </li>
    <li class="top-bar-list-item">
        <button class="dropdown-item" type="submit" name="currencyId" id="top-bar-01918f15b7e2711083e85ec52ac29411" value="01918f15b7e2711083e85ec52ac29411">
            <span aria-hidden="true" class="top-bar-list-item-currency-symbol">$</span>
            US-Dollar
        </button>
    </li>
</ul>
```

If you are modifying the dropdown item, please adjust to the new HTML structure and consider the deprecation comments in the code.
The example below shows `currency-widget.html.twig`. Inside `language-widget.html.twig` a similar structure can be found.

### Before:
```twig
{% sw_extends '@Storefront/storefront/layout/header/actions/currency-widget.html.twig' %}

{% block layout_header_actions_currency_widget_form_items_element_input %}
    <input type="radio">
    Special list-item override
{% endblock %}
```

### After:
```twig
{% sw_extends '@Storefront/storefront/layout/header/actions/currency-widget.html.twig' %}

{# The block `layout_header_actions_currency_widget_form_items_element_input` does no longer exist, use upper block `layout_header_actions_currency_widget_form_items_element_label` insted. #}
{% block layout_header_actions_currency_widget_form_items_element_label %}
    <button class="dropdown-item">
        Special list-item override
    </button>
{% endblock %}
```

## Change Storefront order items and cart line-items from `<div>` to `<ul>` and `<li>`:
To improve the accessibility and semantics, several generic `<div>` elements that are representing lists are changed to actual `<ul>` and `<li>` elements.
This effects the account order overview area as well as the cart line-item templates.

If you are adding custom line-item templates, please change the root element to an `<li>` element:

change
```twig
<div class="{{ lineItemClasses }}">
    <div class="row line-item-row">
        {# Line item content #}
    </div>
<div>
```
to
```twig
<li class="{{ lineItemClasses }}">
    <div class="row line-item-row">
        {# Line item content #}
    </div>
<li>
```

If you are looping over line-items manually in your template, please change the nearest parent element to an `<ul>`:

change
```twig
<div class="line-item-container-custom">
    {% for lineItem in lineItems %}
        {# Now renders `<li>` #}
        {% sw_include '@Storefront/storefront/component/line-item/line-item.html.twig' %}
    {% endfor %}
</div>
```
to
```twig
<ul class="line-item-container-custom list-unstyled">
    {% for lineItem in lineItems %}
        {# Now renders `<li>` #}
        {% sw_include '@Storefront/storefront/component/line-item/line-item.html.twig' %}
    {% endfor %}
</ul>
```

### List of affected templates:
Please consider the documented deprecations inside the templates and adjust modified HTML accordingly.
The overall HTML tree structure and the Twig blocks are not affected by this change.

* Account order overview
  * `src/Storefront/Resources/views/storefront/page/account/order-history/index.html.twig`
  * `src/Storefront/Resources/views/storefront/page/account/order-history/order-detail-document-item.html.twig`
  * `src/Storefront/Resources/views/storefront/page/account/order-history/order-detail-document.html.twig`
* Cart table header (Root element changed to `<li>`)
  * `src/Storefront/Resources/views/storefront/component/checkout/cart-header.html.twig`
* Line-items wrapper (List wrapper element changed to `<ul>`)
  * `src/Storefront/Resources/views/storefront/page/checkout/cart/index.html.twig`
  * `src/Storefront/Resources/views/storefront/page/checkout/confirm/index.html.twig`
  * `src/Storefront/Resources/views/storefront/page/checkout/finish/index.html.twig`
  * `src/Storefront/Resources/views/storefront/page/checkout/address/index.html.twig`
  * `src/Storefront/Resources/views/storefront/page/account/order-history/order-detail-list.html.twig`
  * `src/Storefront/Resources/views/storefront/component/checkout/offcanvas-cart.html.twig`
* Line-items (Root element changed to `<li>`)
  * `src/Storefront/Resources/views/storefront/component/line-item/type/product.html.twig`
  * `src/Storefront/Resources/views/storefront/component/line-item/type/discount.html.twig`
  * `src/Storefront/Resources/views/storefront/component/line-item/type/generic.html.twig`
  * `src/Storefront/Resources/views/storefront/component/line-item/type/container.html.twig`

## Storefront pagination is using anchor links instead of radio inputs
The storefront pagination component (`Resources/views/storefront/component/pagination.html.twig`) is no longer using radio inputs with styled labels. Anchor links are used instead.
If you are modifying the `<label>` inside the pagination template, you need to change the markup to `<a>` instead. Please use one of the documented twig block alternatives inside `pagination.html.twig`.
The hidden radio input will no longer be in the HTML. The current page value will be retrieved by the `data-page` attribute instead of the radio inputs value.

### Before:
```twig
{% sw_extends '@Storefront/storefront/component/pagination.html.twig '%}

{% block component_pagination_first_input %}
    <input type="radio"
           {% if currentPage == 1 %}disabled="disabled"{% endif %}
           name="p"
           id="p-first{{ paginationSuffix }}"
           value="1"
           class="d-none some-special-class"
           title="pagination">
{% endblock %}

{% block component_pagination_first_label %}
    <label class="page-link some-special-class" for="p-first{{ paginationSuffix }}">
        {# Using text instead of icon and add some special CSS class #}
        First
    </label>
{% endblock %}
```

### After:
```twig
{% sw_extends '@Storefront/storefront/component/pagination.html.twig '%}

{# All information that was previously on the radio input, is now also on the anchor link. The id attribute is longer needed. The "disabled" state is now controlled via the parent `<li>` and tabindex. #}
{% block component_pagination_first_link_element %}
    <a href="{{ href ? '?p=1' ~ searchQuery : '#' }}" 
       class="page-link some-special-class"
       data-page="1"
       aria-label="{{ 'general.first'|trans|striptags }}" 
       data-focus-id="first"
       {% if currentPage == 1 %} tabindex="-1" aria-disabled="true"{% endif %}>
        {# Using text instead of icon and add some special CSS class #}
        First
    </a>
{% endblock %}
```
</details>

# Further Changes

# Changed Functionality
Some functionality changed in a way that might be noticeable for merchants. Additionally, this means that changes over the administration (e.g. adjusting configured flows, mail templates) might be needed to adjust to the new behavior.
<details>
  <summary>Detailed Changes</summary>

## Vat Ids will be validated case sensitive
Vat Ids will now be checked for case sensitivity, which means that most Vat Ids will now have to be upper case, depending on their validation pattern.
For customers without a company, this check will only be done on entry, so it is still possible to checkout with an existing lower case Vat Id.
For customers with a company, this check will be done at checkout, so they will need to change their Vat Id to upper case.

## Custom field names and field set names validation
Custom field names and field set names will be validated to not contain hyphens or dots, they must be valid Twig variable names (https://github.com/twigphp/Twig/blob/21df1ad7824ced2abcbd33863f04c6636674481f/src/Lexer.php#L46).
Existing custom fields continue to work, however the validation will be enforced on new custom fields.

## Removal of deprecated properties of `CustomerDeletedEvent`
The deprecated properties `customerId`, `customerNumber`, `customerEmail`, `customerFirstName`, `customerLastName`, `customerCompany` and `customerSalutationId` of `CustomerDeleteEvent` will be removed and cannot be accessed anymore in a mail template when sending a mail via the `Checkout > Customer > Deleted` flow trigger.

## Rule builder: Condition `customerDefaultPaymentMethod` removed
* Removed condition `customerDefaultPaymentMethod` from rule builder, since customers do not have default payment methods anymore
* Existing rules with this condition will be automatically migrated to the new condition `paymentMethod`, so the currently selected payment method

## Flow builder: Trigger `checkout.customer.changed-payment-method` removed
* Removed trigger `checkout.customer.changed-payment-method` from flow builder, since customers do not have default payment methods anymore
* Existing flows will be automatically disabled with Shopware 6.7 and removed in a future, destructive migration

## Direct debit default payment: State change removed
The default payment method "Direct debit" will no longer automatically change the order state to "in progress". Use the flow builder instead, if you want the same behavior.

## New `technicalName` property for payment and shipping methods
The `technicalName` property will be required for payment and shipping methods in the API.
The `technical_name` column will be made non-nullable for the `payment_method` and `shipping_method` tables in the database.

Plugin developers will be required to supply a `technicalName` for their payment and shipping methods.

Merchants must review their custom created payment and shipping methods for the new `technicalName` property and update their methods through the administration accordingly.
</details>

# API
We made some breaks in the API, which might affect your plugins or custom integrations.
<details>
  <summary>Detailed Changes</summary>

## Non spec-compliant /api/oauth/token requests are not supported anymore
Due to an upgrade of the "league/oauth2-server" library, some requests that are not spec-compliant with the OAuth spec are not supported anymore.
Especially scopes now needed to be provided as `scope` parameter and as a space-delimited list of strings.

```diff
grant_type: 'password',
client_id: 'administration',
- scopes: ['write', 'admin'],
+ scope: 'write admin',
username: user,
password: pass,
```
## Removal of /api/oauth/authorize route
Removed API route `/api/oauth/authorize` (`\Core\Framework\Api\Controller\AuthController::authorize` method) without replacement.
</details>

# Core
We made some changes in the PHP core, which might affect your plugins.
<details>
  <summary>Detailed Changes</summary>

## Native types for PHP class properties
All PHP class properties now have a native type.
If you have extended classes with properties, which didn't have a native type before, make sure you now add them as well.

## Reduced data loaded in Store-API Register Route and Register related events

The customer entity does not have all associations loaded by default anymore.
This change reduces the amount of data loaded in the Store-API Register Route and Register related events to improve the performance.

In the following event, the CustomerEntity has no association loaded anymore:

- `\Shopware\Core\Checkout\Customer\Event\CustomerRegisterEvent`
- `\Shopware\Core\Checkout\Customer\Event\CustomerRegisterEvent`
- `\Shopware\Core\Checkout\Customer\Event\CustomerLoginEvent`
- `\Shopware\Core\Checkout\Customer\Event\DoubleOptInGuestOrderEvent`
- `\Shopware\Core\Checkout\Customer\Event\CustomerDoubleOptInRegistrationEvent`

## Payment: Reworked payment handlers
* The payment handlers have been reworked to provide a more flexible and consistent way to handle payments.
* The new AbstractPaymentHandler class should be used to implement payment handlers. A supports method now determines whether the recurring and refund methods can be used for a specific payment method. All other methods are invoked during every payment process, though your payment handler may not need to implement all of them.
* The following interfaces have been deprecated and consolidated into the new `AbstractPaymentHandler`:
  * `AsyncPaymentHandlerInterface`
  * `PreparedPaymentHandlerInterface`
  * `SyncPaymentHandlerInterface`
  * `RefundPaymentHandlerInterface`
  * `RecurringPaymentHandlerInterface`
* Synchronous and asynchronous payments have been unified to return an optional redirect response. This response defines whether the customer is redirected to a payment provider or immediately returned to the order completion page.
* Payment handlers from plugins now receive only the `orderTransactionId`, request information (if applicable, e.g., not for recurring payments), and a `Context`. Any additional data required to process the payment must be retrieved by the payment handler itself to reduce database load. This also minimises dependency on the `SalesChannelContext`, which may contain information that does not accurately reflect the order (e.g., customer addresses may differ from the order’s addresses). For apps, the same information as before is still sent to the app server.

## Payment: Capture step of prepared payments removed
* The method `capture` has been removed from the `PreparedPaymentHandler` interface. This method is no longer being called for apps.
* Use the `pay` method instead for capturing previously validated payments.

## New `technicalName` property for payment and shipping methods
The `technicalName` property will be required for payment and shipping methods in the API.
The `technical_name` column will be made non-nullable for the `payment_method` and `shipping_method` tables in the database.

Plugin developers will be required to supply a `technicalName` for their payment and shipping methods.

Merchants must review their custom created payment and shipping methods for the new `technicalName` property and update their methods through the administration accordingly.

## Customer: Default payment method removed
* Removed default payment method from customer entity, since it was mostly overriden by old saved contexts
* Logic is now more consistent to always be the last used payment method

## Required foreign key in mapping definition for many-to-many associations
If the mapping definition of a many-to-many association does not contain foreign key fields, an exception will be thrown.

## Elasticsearch: Return type of AbstractElasticsearchDefinition::buildTermQuery changed to BuilderInterface

The return type of `\Shopware\Elasticsearch\Framework\AbstractElasticsearchDefinition::buildTermQuery()` and `\Shopware\Elasticsearch\Product\AbstractProductSearchQueryBuilder::build()` changed from BoolQuery to BuilderInterface.
It is not necessary to wrap the return value in a BoolQuery anymore.
Before:
```php
public function buildTermQuery(Context $context, Criteria $criteria): BuilderInterface
{
    $built = $this->searchLogic->build($this->getEntityDefinition()->getEntityName(), $criteria, $context);

    if ($built instanceof BoolQuery) {
        return $built;
    }

    return new BoolQuery([BoolQuery::SHOULD => $built]);
}
```

After:
```php
public function buildTermQuery(Context $context, Criteria $criteria): BuilderInterface
{
    return $this->searchLogic->build($this->getEntityDefinition()->getEntityName(), $criteria, $context);
}
```

## Parameter names of some `\Shopware\Core\Framework\Migration\MigrationStep` changed
* Parameter name `column` of `\Shopware\Core\Framework\Migration\MigrationStep::dropColumnIfExists` changed to `columnName`
* Parameter name `column` of `\Shopware\Core\Framework\Migration\MigrationStep::dropForeignKeyIfExists` changed to `foreignKeyName`
* Parameter name `index` of `\Shopware\Core\Framework\Migration\MigrationStep::dropIndexIfExists` changed to `indexName`

## Changed PromotionGatewayInterface
Changed the return type of the `Shopware\Core\Checkout\Promotion\Gateway\PromotionGatewayInterface` from `EntityCollection<PromotionEntity>` to `PromotionCollection`

## ImportExport signature changes

* Added a new optional parameter `bool $useBatchImport` to `ImportExportFactory::create`. If you extend the `ImportExportFactory` class, you should properly handle the new parameter in your custom implementation.
* Removed method `ImportExportProfileEntity::getName()` and `ImportExportProfileEntity::setName()`. Use `getTechnicalName()` and `setTechnicalName()` instead.
* Removed `profile` attribute from `ImportEntityCommand`. Use `--profile-technical-name` instead.
* Removed `name` field from `ImportExportProfileEntity`.

## SitemapHandleFactoryInterface::create method signature change

We added a new optional parameter `string $domainId` to `SitemapHandleFactoryInterface::create` and `SitemapHandleFactory::create`.
If you implement the `SitemapHandleFactoryInterface` or extend the `SitemapHandleFactory` class, you should properly handle the new parameter in your custom implementation.

## Removal of AuthController::authorize
Removed `\Core\Framework\Api\Controller\AuthController::authorize` method (API route `/api/oauth/authorize`) without replacement.

## TreeUpdater::batchUpdate signature change

We added a new optional parameter `bool $recursive` to `TreeUpdater::batchUpdate`.
If you extend the `TreeUpdater` class, you should properly handle the new parameter in your custom implementation.
```php
<?php

class CustomTreeUpdater extends TreeUpdater
{
    public function batchUpdate(array $updateIds, string $entity, Context $context, bool $recursive = false): void
    {
        parent::batchUpdate($updateIds, $entity, $context, $recursive);
    }
}
```
## removal of \Shopware\Core\Framework\DataAbstractionLayer\Command\CreateSchemaCommand:
`\Shopware\Core\Framework\DataAbstractionLayer\Command\CreateSchemaCommand` will be removed. You can use `\Shopware\Core\Framework\DataAbstractionLayer\Command\CreateMigrationCommand` instead.

## Removal of \Shopware\Core\Framework\DataAbstractionLayer\SchemaGenerator:
`\Shopware\Core\Framework\DataAbstractionLayer\SchemaGenerator` will be removed. You can use `\Shopware\Core\Framework\DataAbstractionLayer\MigrationQueryGenerator` instead.

## AccountService refactoring

The `Shopware\Core\Checkout\Customer\SalesChannel\AccountService::login` method is removed. Use `AccountService::loginByCredentials` or `AccountService::loginById` instead.

Unused constant `Shopware\Core\Checkout\Customer\CustomerException::CUSTOMER_IS_INACTIVE` and unused method `Shopware\Core\Checkout\Customer\CustomerException::inactiveCustomer` are removed.

## Removed `CustomFieldRule` comparison methods:
`floatMatch` and `arrayMatch` methods in `src/Core/Framework/Rule/CustomFieldRule.php` will be removed for Shopware 6.7.0.0

## AbstractCartOrderRoute::order method signature change
The `Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartOrderRoute::order` method will change its signature in the next major version. A new mandatory `request` parameter will be introduced.

## Removal of MailTemplate deprecations
* Removed constants `Shopware\Core\Content\MailTemplate\Subscriber\MailSendSubscriberConfig::{ACTION_NAME,MAIL_CONFIG_EXTENSION}` use `Shopware\Core\Content\Flow\Dispatching\Action\SendMailAction::{ACTION_NAME,MAIL_CONFIG_EXTENSION}` instead
* Removed constant `Shopware\Core\Content\MailTemplate\MailTemplateActions::MAIL_TEMPLATE_MAIL_SEND_ACTION` use `Shopware\Core\Content\Flow\Dispatching\Action\SendMailAction::ACTION_NAME` instead
* Removed class `Shopware\Core\Content\MailTemplate\MailTemplateActions` without replacement
* Removed service `Shopware\Core\Content\MailTemplate\Service\AttachmentLoader` without replacement.
* Removed event `Shopware\Core\Content\MailTemplate\Service\Event\AttachmentLoaderCriteriaEvent` without replacement.

## Unification of Cache constants
* Removed constants `Shopware\Core\Framework\Adapter\Cache\Http\CacheResponseSubscriber::{STATE_LOGGED_IN,STATE_CART_FILLED}` use `Shopware\Core\Framework\Adapter\Cache\CacheStateSubscriber::{STATE_LOGGED_IN,STATE_CART_FILLED}` instead
* Removed constants `Shopware\Core\Framework\Adapter\Cache\Http\CacheResponseSubscriber::{CURRENCY_COOKIE,CONTEXT_CACHE_COOKIE,SYSTEM_STATE_COOKIE,INVALIDATION_STATES_HEADER}` use `Shopware\Core\Framework\Adapter\Cache\Http\HttpCacheKeyGenerator::{CURRENCY_COOKIE,CONTEXT_CACHE_COOKIE,SYSTEM_STATE_COOKIE,INVALIDATION_STATES_HEADER}` instead

## Domain Exception Handling
We have changed/removed some exception classes in accordance with the [domain exception handling ADR](./adr/2022-02-24-domain-exceptions.md).
<details>
  <summary>See the detailed list</summary>

## Removal of ConfigurationNotFoundException
* Removed `\Shopware\Core\System\SystemConfig\Exception\ConfigurationNotFoundException`. Use `\Shopware\Core\System\SystemConfig\SystemConfigException::configurationNotFound` instead.
* Removed `Shopware\Core\System\Snippet\Exception\FilterNotFoundException`. Use `Shopware\Core\System\Snippet\SnippetException::filterNotFound` instead.
* Removed `Shopware\Core\System\Snippet\Exception\InvalidSnippetFileException`. Use `Shopware\Core\System\Snippet\SnippetException::invalidSnippetFile` instead.

## Changed thrown exceptions in `TranslationsSerializer`
Changed the `InvalidArgumentException`, which was thrown in `TranslationsSerializer::serialize` and `TranslationsSerializer::deserialize` when the given association field wasn't a `TranslationsAssociationField`, to the new `ImportExportException::invalidInstanceType` exception.

## Deprecated ImportExport domain exception
Deprecated method `\Shopware\Core\Content\ImportExport\ImportExportException::invalidInstanceType`. Thrown exception will change from `InvalidArgumentException` to `ImportExportException`.

## Removal of obsolete method in DefinitionValidator
The method `\Shopware\Core\Framework\DataAbstractionLayer\DefinitionValidator::getNotices` was removed.

## Removal of deprecated exceptions
The following exceptions were removed:
* `\Shopware\Core\Framework\Api\Exception\UnsupportedEncoderInputException`
* `\Shopware\Core\Framework\DataAbstractionLayer\Exception\CanNotFindParentStorageFieldException`
* `\Shopware\Core\Framework\DataAbstractionLayer\Exception\InternalFieldAccessNotAllowedException`
* `\Shopware\Core\Framework\DataAbstractionLayer\Exception\InvalidParentAssociationException`
* `\Shopware\Core\Framework\DataAbstractionLayer\Exception\ParentFieldNotFoundException`
* `\Shopware\Core\Framework\DataAbstractionLayer\Exception\PrimaryKeyNotProvidedException`

## Entity class throws different exceptions
The following methods of the `\Shopware\Core\Framework\DataAbstractionLayer\Entity` class are now throwing different exceptions:
* `\Shopware\Core\Framework\DataAbstractionLayer\Entity::__get` now throws a `\Shopware\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException` instead of a `\Shopware\Core\Framework\DataAbstractionLayer\Exception\InternalFieldAccessNotAllowedException`.
* `\Shopware\Core\Framework\DataAbstractionLayer\Entity::get` now throws a `\Shopware\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException` instead of a `\Shopware\Core\Framework\DataAbstractionLayer\Exception\InternalFieldAccessNotAllowedException`.
* `\Shopware\Core\Framework\DataAbstractionLayer\Entity::checkIfPropertyAccessIsAllowed` now throws a `\Shopware\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException` instead of a `\Shopware\Core\Framework\DataAbstractionLayer\Exception\InternalFieldAccessNotAllowedException`.
* `\Shopware\Core\Framework\DataAbstractionLayer\Entity::get` now throws a `\Shopware\Core\Framework\DataAbstractionLayer\Exception\PropertyNotFoundException` instead of a `\InvalidArgumentException`.
</details>
</details>

# Administration
We made some changes in the administration, which might affect your plugins.
<details>
  <summary>Detailed Changes</summary>

## Administration removed associations
* Removed `calculationRule` association in `shippingMethodCriteria()` in `sw-settings-shipping-detail`.
* Removed `conditions` association in `ruleFilterCriteria()` and `shippingRuleFilterCriteria()` in `sw-settings-shipping-price-matrix`

## Removal of sw-dashboard-statistics and associated component sections and data sets
The component `sw-dashboard-statistics` (`src/module/sw-dashboard/component/sw-dashboard-statistics`) has been removed without replacement.

The associated component sections `sw-chart-card__before` and `sw-chart-card__after` were removed, too.
Use `sw-dashboard__before-content` and `sw-dashboard__after-content` instead.

Before:
```js
import { ui } from '@shopware-ag/meteor-admin-sdk';

ui.componentSection.add({
    positionId: 'sw-chart-card__before',
    ...
})
```

After:
```js
import { ui } from '@shopware-ag/meteor-admin-sdk';

ui.componentSection.add({
    positionId: 'sw-dashboard__before-content',
    ...
})
```

Additionally, the associated data sets `sw-dashboard-detail__todayOrderData` and `sw-dashboard-detail__statisticDateRanges` were removed.
In both cases, use the Admin API instead.

## Replace `isEmailUsed` with `isEmailAlreadyInUse`:
Replace `isEmailUsed` with `isEmailAlreadyInUse` in `sw-users-permission-user-detail`.

## Component replacement with Meteor Component Library
We switched the usage of basic components from custom components to the meteor component library. For more details take a look at the [according ADR](./adr/2024-03-21-implementation-of-meteor-component-library.md).

**More information about how you can automate the update with `codemods` will be available soon.**

In short this means we replaced the following components:
* `sw-popover` with `mt-floating-ui`
* `sw-tabs` with `mt-tabs`
* `sw-select-field` with `mt-select`
* `sw-textarea-field` with `mt-textarea`
* `sw-datepicker` with `mt-datepicker`
* `sw-password-field` with `mt-password-field`
* `sw-colorpicker` with `mt-colorpicker`
* `sw-external-link` with `mt-external-link`
* `sw-skeleton-bar` with `mt-skeleton-bar`
* `sw-email-field` with `mt-email-field`
* `sw-url-field` with `mt-url-field`
* `sw-progress-bar` with `mt-progress-bar`
* `sw-button` with `mt-button`
* `sw-icon` with `mt-icon`
* `sw-card` with `mt-card`
* `sw-text-field` with `mt-text-field`
* `sw-switch-field` with `mt-switch`
* `sw-number-field` with `mt-number-field`
* `sw-loader` with `mt-loader`
* `sw-checkbox-field` with `mt-checkbox`

<details>
    <summary>See the detailed list</summary>

## Removal of "sw-popover":
The old "sw-popover" component will be removed in the next major version. Please use the new "mt-floating-ui" component instead.

We will provide you with a codemod (ESLint rule) to automatically convert your codebase to use the new "mt-floating-ui" component. This component is much different from the old "sw-popover" component, so the codemod will not be able to convert all occurrences. You will have to manually adjust some parts of your codebase. For this you can look at the Storybook documentation for the Meteor Component Library.

If you don't want to use the codemod, you can manually replace all occurrences of "sw-popover" with "mt-floating-ui".

Following changes are necessary:

### "sw-popover" is removed
Replace all component names from "sw-popover" with "mt-floating-ui"

Before:
```html
<sw-popover />
```
After:
```html
<mt-floating-ui />
```

### "mt-floating-ui" has no property "zIndex" anymore
The property "zIndex" is removed without a replacement.

Before:
```html
<sw-popover :zIndex="myZIndex" />
```
After:
```html
<mt-floating-ui />
```

### "mt-floating-ui" has no property "resizeWidth" anymore
The property "resizeWidth" is removed without a replacement.

Before:
```html
<sw-popover :resizeWidth="myWidth" />
```

After:
```html
<mt-floating-ui />
```

### "mt-floating-ui" has no property "popoverClass" anymore
The property "popoverClass" is removed without a replacement.

Before:
```html
<sw-popover popoverClass="my-class" />
```
After:
```html
<mt-floating-ui />
```

### "mt-floating-ui" is not open by default anymore
The "open" property is removed. You have to control the visibility of the popover by yourself with the property "isOpened".

Before:
```html
<sw-popover />
```
After:
```html
<mt-floating-ui :isOpened="myVisibility" />
```

## Removal of "sw-tabs":
The old "sw-tabs" component will be removed in the next major version. Please use the new "mt-tabs" component instead.

We will provide you with a codemod (ESLint rule) to automatically convert your codebase to use the new "mt-tabs" component. In this specific component it cannot convert anything correctly, because the new "mt-tabs" component has a different API. You have to manually check and solve every "TODO" comment created by the codemod.

If you don't want to use the codemod, you can manually replace all occurrences of "sw-tabs" with "mt-tabs".

Following changes are necessary:

### "sw-tabs" is removed
Replace all component names from "sw-tabs" with "mt-tabs"

Before:
```html
<sw-tabs />
```
After:
```html
<mt-tabs />
```

### "sw-tabs" wrong "default" slot usage will be replaced with "items" property
You need to replace the "default" slot with the "items" property. The "items" property is an array of objects which are used to render the tabs. Using the "sw-tabs-item" component is not needed anymore.

Before:
```html
<sw-tabs>
    <template #default="{ active }">
        <sw-tabs-item name="tab1">Tab 1</sw-tabs-item>
        <sw-tabs-item name="tab2">Tab 2</sw-tabs-item>
    </template>
</sw-tabs>
```

After:
```html
<mt-tabs :items="[
    {
        'label': 'Tab 1',
        'name': 'tab1'
    },
    {
        'label': 'Tab 2',
        'name': 'tab2'
    }
]">
</mt-tabs>
```

### "sw-tabs" wrong "content" slot usage - content should be set manually outside the component
The content slot is not supported anymore. You need to set the content manually outside the component. You can use the "new-item-active" event to get the active item and set it to a variable. Then you can use this variable anywere in your template.

Before:
```html
<sw-tabs>
    <template #content="{ active }">
        The current active item is {{ active }}
    </template>
</sw-tabs>
```

After:
```html
<!-- setActiveItem need to be defined -->
<mt-tabs @new-item-active="setActiveItem"></mt-tabs>

The current active item is {{ activeItem }}
```

### "sw-tabs" property "isVertical" was renamed to "vertical"
Before:
```html
<sw-tabs is-vertical />
```

After:
```html
<mt-tabs vertical />
```

### "sw-tabs" property "alignRight" was removed
Before:
```html
<sw-tabs align-right />
```

After:
```html
<mt-tabs />
```
## Removal of "sw-select-field":
The old "sw-select-field" component will be removed in the next major version. Please use the new "mt-select" component instead.

We will provide you with a codemod (ESLint rule) to automatically convert your codebase to use the new "mt-select" component.

If you don't want to use the codemod, you can manually replace all occurrences of "sw-select-field" with "mt-select".

Following changes are necessary:

### "sw-select-field" is removed
Replace all component names from "sw-select-field" with "mt-select"

Before:
```html
<sw-select-field />
```
After:
```html
<mt-select />
```

### "sw-select-field" prop "value" was renamed to "modelValue"
Replace all occurrences of the prop "value" with "modelValue"

Before:
```html
<sw-select-field :value="selectedValue" />
```

After:
```html
<mt-select :modelValue="selectedValue" />
```

### "sw-select-field" the "v-model:value" was renamed to "v-model"
Replace all occurrences of the "v-model:value" directive with "v-model"

Before:
```html
<sw-select-field v-model:value="selectedValue" />
```

After:
```html
<mt-select v-model="selectedValue" />
```

### "sw-select-field" the prop "options" expect a different format
The prop "options" now expects an array of objects with the properties "label" and "value". The old format with "name" and "id" is not supported anymore.

Before:
```html
<sw-select-field :options="[ { name: 'Option 1', id: 1 }, { name: 'Option 2', id: 2 } ]" />
```

After:
```html
<mt-select :options="[ { label: 'Option 1', value: 1 }, { label: 'Option 2', value: 2 } ]" />
```

### "sw-select-field" the prop "aside" was removed
The prop "aside" was removed without replacement.

Before:
```html
<sw-select-field :aside="true" />
```

After:
```html
<mt-select />
```

### "sw-select-field" the default slot was removed
The default slot was removed. The options are now passed via the "options" prop.

Before:
```html
<sw-select-field>
    <option value="1">Option 1</option>
    <option value="2">Option 2</option>
</sw-select-field>
```

After:
```html
<mt-select :options="[ { label: 'Option 1', value: 1 }, { label: 'Option 2', value: 2 } ]" />
```

### "sw-select-field" the label slot was removed
The label slot was removed. The label is now passed via the "label" prop.

Before:
```html
<sw-select-field>
    <template #label>
        My Label
    </template>
</sw-select-field>
```

After:
```html
<mt-select label="My Label" />
```

### "sw-select-field" the event "update:value" was renamed to "update:modelValue"
The event "update:value" was renamed to "update:modelValue"

Before:
```html
<sw-select-field @update:value="onUpdateValue" />
```

After:
```html
<mt-select @update:modelValue="onUpdateValue" />
```
## Removal of "sw-textarea-field":
The old "sw-textarea-field" component will be removed in the next major version. Please use the new "mt-textarea" component instead.

We will provide you with a codemod (ESLint rule) to automatically convert your codebase to use the new "mt-textarea" component. In this specific component it cannot convert anything correctly, because the new "mt-textarea" component has a different API. You have to manually check and solve every "TODO" comment created by the codemod.

If you don't want to use the codemod, you can manually replace all occurrences of "sw-textarea-field" with "mt-textarea".

Following changes are necessary:

### "sw-textarea-field" is removed
Replace all component names from "sw-textarea-field" with "mt-textarea"

Before:
```html
<sw-textarea-field />
```
After:
```html
<mt-textarea />
```

### "sw-textarea-field" property "value" is replaced by "modelValue"
Replace all occurrences of the property "value" with "modelValue"

Before:
```html
<sw-textarea-field :value="myValue" />
```
After:
```html
<mt-textarea :modelValue="myValue" />
```

### "sw-textarea-field" binding "v-model:value" is replaced by "v-model"
Replace all occurrences of the binding "v-model:value" with "v-model"

Before:
```html
<sw-textarea-field v-model:value="myValue" />
```

After:
```html
<mt-textarea v-model="myValue" />
```

### "sw-textarea-field" slot "label" is replaced by property "label"
Replace all occurrences of the slot "label" with the property "label"

Before:
```html
<sw-textarea-field>
    <template #label>
        My Label
    </template>
</sw-textarea-field>
```

After:
```html
<mt-textarea label="My Label" />
```

### "sw-textarea-field" event "update:value" is replaced by "update:modelValue"
Replace all occurrences of the event "update:value" with "update:modelValue"

Before:
```html
<sw-textarea-field @update:value="onUpdateValue" />
```

After:
```html
<mt-textarea @update:modelValue="onUpdateValue" />
```
## Removal of "sw-datepicker":
The old "sw-datepicker" component will be removed in the next major version. Please use the new "mt-datepicker" component instead.

We will provide you with a codemod (ESLint rule) to automatically convert your codebase to use the new "mt-datepicker" component. In this specific component it cannot convert anything correctly, because the new "mt-datepicker" component has a different API. You have to manually check and solve every "TODO" comment created by the codemod.

If you don't want to use the codemod, you can manually replace all occurrences of "sw-datepicker" with "mt-datepicker".

Following changes are necessary:

### "sw-datepicker" is removed
Replace all component names from "sw-datepicker" with "mt-datepicker"

Before:
```html
<sw-datepicker />
```
After:
```html
<mt-datepicker />
```

### "sw-datepicker" property "value" is replaced by "modelValue"
Replace all occurrences of the property "value" with "modelValue"

Before:
```html
<sw-datepicker :value="myValue" />
```
After:
```html
<mt-datepicker :modelValue="myValue" />
```

### "sw-datepicker" binding "v-model:value" is replaced by "v-model"
Replace all occurrences of the binding "v-model:value" with "v-model"

Before:
```html
<sw-datepicker v-model:value="myValue" />
```

After:
```html
<mt-datepicker v-model="myValue" />
```

### "sw-datepicker" slot "label" is replaced by property "label"
Replace all occurrences of the slot "label" with the property "label"

Before:
```html
<sw-datepicker>
    <template #label>
        My Label
    </template>
</sw-datepicker>
```

After:
```html
<mt-datepicker label="My Label" />
```

### "sw-datepicker" event "update:value" is replaced by "update:modelValue"
Replace all occurrences of the event "update:value" with "update:modelValue"

Before:
```html
<sw-datepicker @update:value="onUpdateValue" />
```

After:
```html
<mt-datepicker @update:modelValue="onUpdateValue" />
```
## Removal of "sw-password-field":
The old "sw-password-field" component will be removed in the next major version. Please use the new "mt-password-field" component instead.

We will provide you with a codemod (ESLint rule) to automatically convert your codebase to use the new "mt-password-field" component.

If you don't want to use the codemod, you can manually replace all occurrences of "sw-password-field" with "mt-password-field".

Following changes are necessary:

### "sw-password-field" is removed
Replace all component names from "sw-password-field" with "mt-password-field"

Before:
```html
<sw-password-field>Hello World</sw-password-field>
```
After:
```html
<mt-password-field>Hello World</mt-password-field>
```

### "mt-password-field" has no property "value" anymore
Replace all occurrences of the "value" prop with "modelValue"

Before:
```html
<sw-password-field value="Hello World" />
```
After:
```html
<mt-password-field modelValue="Hello World" />
```

### "mt-password-field" v-model:value is deprecated
Replace all occurrences of the "v-model:value" directive with "v-model"

Before:
```html
<sw-password-field v-model:value="myValue" />
```
After:
```html
<mt-password-field v-model="myValue" />
```

### "mt-password-field" has no property "size" with value "medium" anymore
Replace all occurrences of the "size" prop with "default"

Before:
```html
<sw-password-field size="medium" />
```
After:
```html
<mt-password-field size="default" />
```

### "mt-password-field" has no property "isInvalid" anymore
Remove all occurrences of the "isInvalid" prop

Before:
```html
<sw-password-field isInvalid />
```
After:
```html
<mt-password-field />
```

### "mt-password-field" has no event "update:value" anymore
Replace all occurrences of the "update:value" event with "update:modelValue"

Before:
```html
<sw-password-field @update:value="updateValue" />
```

After:
```html
<mt-password-field @update:modelValue="updateValue" />
```

### "mt-password-field" has no event "base-field-mounted" anymore
Remove all occurrences of the "base-field-mounted" event

Before:
```html
<sw-password-field @base-field-mounted="onFieldMounted" />
```
After:
```html
<mt-password-field />
```

### "mt-password-field" has no slot "label" anymore
Remove all occurrences of the "label" slot. The slot content should be moved to the "label" prop. Only string values are supported. Other slot content is not supported
anymore.

Before:
```html
<sw-password-field>
    <template #label>
        My Label
    </template>
</sw-password-field>
```
After:
```html
<mt-password-field label="My label">
</mt-password-field>
```

### "mt-password-field" has no slot "hint" anymore
Remove all occurrences of the "hint" slot. The slot content should be moved to the "hint" prop. Only string values are supported. Other slot content is not supported

Before:
```html
<sw-password-field>
    <template #hint>
        My Hint
    </template>
</sw-password-field>
```
After:
```html
<mt-password-field hint="My hint">
</mt-password-field>
```
## Removal of "sw-colorpicker":
The old "sw-colorpicker" component will be removed in the next major version. Please use the new "mt-colorpicker" component instead.

We will provide you with a codemod (ESLint rule) to automatically convert your codebase to use the new "mt-colorpicker" component. In this specific component it cannot convert anything correctly, because the new "mt-colorpicker" component has a different API. You have to manually check and solve every "TODO" comment created by the codemod.

If you don't want to use the codemod, you can manually replace all occurrences of "sw-colorpicker" with "mt-colorpicker".

Following changes are necessary:

### "sw-colorpicker" is removed
Replace all component names from "sw-colorpicker" with "mt-colorpicker"

Before:
```html
<sw-colorpicker />
```
After:
```html
<mt-colorpicker />
```

### "sw-colorpicker" property "value" is replaced by "modelValue"
Replace all occurrences of the property "value" with "modelValue"

Before:
```html
<sw-colorpicker :value="myValue" />
```
After:
```html
<mt-colorpicker :modelValue="myValue" />
```

### "sw-colorpicker" binding "v-model:value" is replaced by "v-model"
Replace all occurrences of the binding "v-model:value" with "v-model"

Before:
```html
<sw-colorpicker v-model:value="myValue" />
```

After:
```html
<mt-colorpicker v-model="myValue" />
```

### "sw-colorpicker" slot "label" is replaced by property "label"
Replace all occurrences of the slot "label" with the property "label"

Before:
```html
<sw-colorpicker>
    <template #label>
        My Label
    </template>
</sw-colorpicker>
```

After:
```html
<mt-colorpicker label="My Label" />
```

### "sw-colorpicker" event "update:value" is replaced by "update:modelValue"
Replace all occurrences of the event "update:value" with "update:modelValue"

Before:
```html
<sw-colorpicker @update:value="onUpdateValue" />
```

After:
```html
<mt-colorpicker @update:modelValue="onUpdateValue" />
```
## Removal of "sw-external-link":
The old "sw-external-link" component will be removed in the next major version. Please use the new "mt-external-link" component instead.

We will provide you with a codemod (ESLint rule) to automatically convert your codebase to use the new "mt-external-link" component.

If you don't want to use the codemod, you can manually replace all occurrences of "sw-external-link" with "mt-external-link".

Following changes are necessary:

### "sw-external-link" is removed
Replace all component names from "sw-external-link" with "mt-external-link"

Before:
```html
<sw-external-link>Hello World</sw-external-link>
```
After:
```html
<mt-external-link>Hello World</mt-external-link>
```

### "sw-external-link" property "icon" is removed
The "icon" property is removed from the "mt-external-link" component. There is no replacement for this property.

Before:
```html
<sw-external-link icon="world">Hello World</sw-external-link>
```
After:
```html
<mt-external-link>Hello World</mt-external-link>
```
## Removal of "sw-skeleton-bar":
The old "sw-skeleton-bar" component will be removed in the next major version. Please use the new "mt-skeleton-bar" component instead.

We will provide you with a codemod (ESLint rule) to automatically convert your codebase to use the new "mt-skeleton-bar" component.

If you don't want to use the codemod, you can manually replace all occurrences of "sw-skeleton-bar" with "mt-skeleton-bar".

Following changes are necessary:

### "sw-skeleton-bar" is removed
Replace all component names from "sw-skeleton-bar" with "mt-skeleton-bar"

Before:
```html
<sw-skeleton-bar>Hello World</sw-skeleton-bar>
```
After:
```html
<mt-skeleton-bar>Hello World</mt-skeleton-bar>
```
## Removal of "sw-email-field":
The old "sw-email-field" component will be removed in the next major version. Please use the new "mt-email-field" component instead.

We will provide you with a codemod (ESLint rule) to automatically convert your codebase to use the new "mt-email-field" component.

If you don't want to use the codemod, you can manually replace all occurrences of "sw-email-field" with "mt-email-field".

Following changes are necessary:

### "sw-email-field" is removed
Replace all component names from "sw-email-field" with "mt-email-field"

Before:
```html
<sw-email-field>Hello World</sw-email-field>
```
After:
```html
<mt-email-field>Hello World</mt-email-field>
```

### "mt-email-field" has no property "value" anymore
Replace all occurrences of the "value" prop with "modelValue"

Before:
```html
<mt-email-field value="Hello World" />
```
After:
```html
<mt-email-field modelValue="Hello World" />
```

### "mt-email-field" v-model:value is deprecated
Replace all occurrences of the "v-model:value" directive with "v-model"

Before:
```html
<mt-email-field v-model:value="myValue" />
```
After:
```html
<mt-email-field v-model="myValue" />
```

### "mt-email-field" has no property "size" with value "medium" anymore
Replace all occurrences of the "size" prop with "default"

Before:
```html
<mt-email-field size="medium" />
```
After:
```html
<mt-email-field size="default" />
```

### "mt-email-field" has no property "isInvalid" anymore
Remove all occurrences of the "isInvalid" prop

Before:
```html
<mt-email-field isInvalid />
```
After:
```html
<mt-email-field />
```

### "mt-email-field" has no property "aiBadge" anymore
Remove all occurrences of the "aiBadge" prop

Before:
```html
<mt-email-field aiBadge />
```
After:
```html
<mt-email-field />
```

### "mt-email-field" has no event "update:value" anymore
Replace all occurrences of the "update:value" event with "update:modelValue"

Before:
```html
<mt-email-field @update:value="updateValue" />
```

After:
```html
<mt-email-field @update:modelValue="updateValue" />
```

### "mt-email-field" has no event "base-field-mounted" anymore
Remove all occurrences of the "base-field-mounted" event

Before:
```html
<mt-email-field @base-field-mounted="onFieldMounted" />
```
After:
```html
<mt-email-field />
```

### "mt-email-field" has no slot "label" anymore
Remove all occurrences of the "label" slot. The slot content should be moved to the "label" prop. Only string values are supported. Other slot content is not supported
anymore.

Before:
```html
<mt-email-field>
    <template #label>
        My Label
    </template>
</mt-email-field>
```
After:
```html
<mt-email-field label="My label">
</mt-email-field>
```
## Removal of "sw-url-field":
The old "sw-url-field" component will be removed in the next major version. Please use the new "mt-url-field" component instead.

We will provide you with a codemod (ESLint rule) to automatically convert your codebase to use the new "mt-url-field" component.

If you don't want to use the codemod, you can manually replace all occurrences of "sw-url-field" with "mt-url-field".

Following changes are necessary:

### "sw-url-field" is removed
Replace all component names from "sw-url-field" with "mt-url-field"

Before:
```html
<sw-url-field />
```
After:
```html
<mt-url-field />
```

### "mt-url-field" has no property "value" anymore
Replace all occurrences of the "value" prop with "modelValue"

Before:
```html
<sw-url-field value="Hello World" />
```
After:
```html
<mt-url-field modelValue="Hello World" />
```

### "mt-url-field" v-model:value is deprecated
Replace all occurrences of the "v-model:value" directive with "v-model"

Before:
```html
<sw-url-field v-model:value="myValue" />
```
After:
```html
<mt-url-field v-model="myValue" />
```

### "mt-url-field" has no event "update:value" anymore
Replace all occurrences of the "update:value" event with "update:modelValue"

Before:
```html
<sw-url-field @update:value="updateValue" />
```

After:
```html
<mt-url-field @update:modelValue="updateValue" />
```

### "mt-url-field" has no slot "label" anymore
Remove all occurrences of the "label" slot. The slot content should be moved to the "label" prop. Only string values are supported. Other slot content is not supported
anymore.

Before:
```html
<sw-url-field>
    <template #label>
        My Label
    </template>
</sw-url-field>
```
After:
```html
<mt-url-field label="My label">
</mt-url-field>
```

### "mt-url-field" has no slot "hint" anymore
Remove all occurrences of the "hint" slot. There is no replacement for this slot.

Before:
```html
<sw-url-field>
    <template #hint>
        My Hint
    </template>
</sw-url-field>
```

After:
```html
<mt-url-field />
```
### Removal of "sw-progress-bar":
The old "sw-progress-bar" component will be removed in the next major version. Please use the new "mt-progress-bar" component instead.

We will provide you with a codemod (ESLint rule) to automatically convert your codebase to use the new "mt-progress-bar" component.

If you don't want to use the codemod, you can manually replace all occurrences of "sw-progress-bar" with "mt-progress-bar".

Following changes are necessary:

### "sw-progress-bar" is removed
Replace all component names from "sw-progress-bar" with "mt-progress-bar"

Before:
```html
<sw-progress-bar />
```
After:
```html
<mt-progress-bar />
```

### "mt-progress-bar" has no property "value" anymore
Replace all occurrences of the "value" prop with "modelValue"

Before:
```html
<mt-progress-bar value="5" />
```
After:
```html
<mt-progress-bar modelValue="5" />
```

### "mt-progress-bar" v-model:value is deprecated
Replace all occurrences of the "v-model:value" directive with "v-model"

Before:
```html
<mt-progress-bar v-model:value="myValue" />
```
After:
```html
<mt-progress-bar v-model="myValue" />
```

### "mt-progress-bar" has no event "update:value" anymore
Replace all occurrences of the "update:value" event with "update:modelValue"

Before:
```html
<mt-progress-bar @update:value="updateValue" />
```

After:
```html
<mt-progress-bar @update:modelValue="updateValue" />
```

## Removal of "sw-button":
The old "sw-button" component will be removed in the next major version. Please use the new "mt-button" component instead.

We will provide you with a codemod (ESLint rule) to automatically convert your codebase to use the new "mt-button" component.

If you don't want to use the codemod, you can manually replace all occurrences of "sw-button" with "mt-button".

Following changes are necessary:

### "sw-button" is removed
Replace all component names from "sw-button" with "mt-button"

Before:
```html
<sw-button>Save</sw-button>
```
After:
```html
<mt-button>Save</mt-button>
```

### "mt-button" has no value "ghost" in property "variant" anymore
Remove the property "variant". Use the property "ghost" instead.

Before:
```html
<sw-button variant="ghost">Save</sw-button>
```
After:
```html
<mt-button ghost>Save</mt-button>
```

### "mt-button" has no value "danger" in property "variant" anymore
Replace the value "danger" with "critical" in the property "variant".

Before:
```html
<sw-button variant="danger">Delete</sw-button>
```
After:
```html
<mt-button variant="critical">Delete</mt-button>
```

### "mt-button" has no value "ghost-danger" in property "variant" anymore
Replace the value "ghost-danger" with "critical" in the property "variant". Add the property "ghost".

Before:
```html
<sw-button variant="ghost-danger">Delete</sw-button>
```
After:
```html
<mt-button variant="critical" ghost>Delete</mt-button>
```

### "mt-button" has no value "contrast" in property "variant" anymore
Remove the value "contrast" from the property "variant". There is no replacement.

### "mt-button" has no value "context" in property "variant" anymore
Remove the value "context" from the property "variant". There is no replacement.

### "mt-button" has no property "router-link" anymore
Replace the property "router-link" with a "@click" event listener and a "this.$router.push()" method.

Before:
```html
<sw-button router-link="sw.example.route">Go to example</sw-button>
```
After:
```html
<mt-button @click="this.$router.push('sw.example.route')">Go to example</mt-button>
```

## Removal of "sw-icon":
The old "sw-icon" component will be removed in the next major version. Please use the new "mt-icon" component instead.

We will provide you with a codemod (ESLint rule) to automatically convert your codebase to use the new "mt-icon" component.

If you don't want to use the codemod, you can manually replace all occurrences of "sw-icon" with "mt-icon".

Following changes are necessary:

### "sw-icon" is removed
Replace all component names from "sw-icon" with "mt-icon"

Before:
```html
<sw-icon name="regular-times-s" />
```
After:
```html
<mt-icon name="regular-times-s" />
```

### "mt-icon" has no property "small" anymore
Replace the property "small" with "size" of value "16px" if used

Before:
```html
<sw-icon name="regular-times-s" small />
```
After:
```html
<mt-icon name="regular-times-s" size="16px" />
```

### "mt-icon" has no property "large" anymore
Replace the property "large" with "size" of value "32px" if used

Before:
```html
<sw-icon name="regular-times-s" large />
```

After:
```html
<mt-icon name="regular-times-s" size="32px" />
```

### "mt-icon" has different default sizes than "sw-icon"
If no property "size", "small" or "large" is used, you need to use the "size" prop with the value "24px" to avoid a different default size than with "sw-icon"

Before:
```html
<sw-icon name="regular-times-s" />
```
After:
```html
<mt-icon name="regular-times-s" size="24px" />
```
## Removal of "sw-card":
The old "sw-card" component will be removed in the next major version. Please use the new "mt-card" component instead.

We will provide you with a codemod (ESLint rule) to automatically convert your codebase to use the new "mt-card" component.

If you don't want to use the codemod, you can manually replace all occurrences of "sw-card" with "mt-card".

Following changes are necessary:

### "sw-card" is removed
Replace all component names from "sw-card" with "mt-card"

Before:
```html
<sw-card>Hello World</sw-card>
```
After:
```html
<mt-card>Hello World</mt-card>
```

### "mt-card" has no property "aiBadge" anymore
Replace the property "aiBadge" by using the "sw-ai-copilot-badge" component directly inside the "title" slot

Before:
```html
<mt-card aiBadge>Hello Wolrd</mt-card>
```

After:
```html
<mt-card>
    <slot name="title"><sw-ai-copilot-badge /></slot>
    Hello World
</mt-card>
```

### "mt-card" has no property "contentPadding" anymore
The property "contentPadding" is removed without a replacement.

Before:
```html
<mt-card contentPadding>Hello World</mt-card>
```

After:
```html
<mt-card>Hello World</mt-card>
```

## Removal of "sw-text-field":
The old "sw-text-field" component will be removed in the next major version. Please use the new "mt-text-field" component instead.

We will provide you with a codemod (ESLint rule) to automatically convert your codebase to use the new "mt-text-field" component.

If you don't want to use the codemod, you can manually replace all occurrences of "sw-text-field" with "mt-text-field".

Following changes are necessary:

### "sw-text-field" is removed
Replace all component names from "sw-text-field" with "mt-text-field"

Before:
```html
<sw-text-field>Hello World</sw-text-field>
```
After:
```html
<mt-text-field>Hello World</mt-text-field>
```

### "mt-text-field" has no property "value" anymore
Replace all occurrences of the "value" prop with "modelValue"

Before:
```html
<mt-text-field value="Hello World" />
```
After:
```html
<mt-text-field modelValue="Hello World" />
```

### "mt-text-field" v-model:value is deprecated
Replace all occurrences of the "v-model:value" directive with "v-model"

Before:
```html
<mt-text-field v-model:value="myValue" />
```
After:
```html
<mt-text-field v-model="myValue" />
```

### "mt-text-field" has no property "size" with value "medium" anymore
Replace all occurrences of the "size" prop with "default"

Before:
```html
<mt-text-field size="medium" />
```
After:
```html
<mt-text-field size="default" />
```

### "mt-text-field" has no property "isInvalid" anymore
Remove all occurrences of the "isInvalid" prop

Before:
```html
<mt-text-field isInvalid />
```
After:
```html
<mt-text-field />
```

### "mt-text-field" has no property "aiBadge" anymore
Remove all occurrences of the "aiBadge" prop

Before:
```html
<mt-text-field aiBadge />
```
After:
```html
<mt-text-field />
```

### "mt-text-field" has no event "update:value" anymore
Replace all occurrences of the "update:value" event with "update:modelValue"

Before:
```html
<mt-text-field @update:value="updateValue" />
```

After:
```html
<mt-text-field @update:modelValue="updateValue" />
```

### "mt-text-field" has no event "base-field-mounted" anymore
Remove all occurrences of the "base-field-mounted" event

Before:
```html
<mt-text-field @base-field-mounted="onFieldMounted" />
```
After:
```html
<mt-text-field />
```

### "mt-text-field" has no slot "label" anymore
Remove all occurrences of the "label" slot. The slot content should be moved to the "label" prop. Only string values are supported. Other slot content is not supported
anymore.

Before:
```html
<mt-text-field>
    <template #label>
        My Label
    </template>
</mt-text-field>
```
After:
```html
<mt-text-field label="My label">
</mt-text-field>
```
## Removal of "sw-switch-field":
The old "sw-switch-field" component will be removed in the next major version. Please use the new "mt-switch" component instead.

We will provide you with a codemod (ESLint rule) to automatically convert your codebase to use the new "mt-switch" component.

If you don't want to use the codemod, you can manually replace all occurrences of "sw-switch-field" with "mt-switch".

Following changes are necessary:

### "sw-switch-field" is removed
Replace all component names from "sw-switch-field" with "mt-switch".

Before:
```html
<sw-switch-field>Hello World</sw-switch-field>
```
After:
```html
<mt-switch>Hello World</mt-switch>
```

### "mt-switch" has no "noMarginTop" prop anymore
Replace all occurrences of the "noMarginTop" prop with "removeTopMargin".

Before:
```html
<mt-switch noMarginTop />
```
After:
```html
<mt-switch removeTopMargin />
```

### "mt-switch" has no "size" prop anymore
Remove all occurrences of the "size" prop.

Before:
```html
<mt-switch size="small" />
```

After:
```html
<mt-switch />
```

### "mt-switch" has no "id" prop anymore
Remove all occurrences of the "id" prop.

Before:
```html
<mt-switch id="example-identifier" />
```

After:
```html
<mt-switch />
```

### "mt-switch" has no "value" prop anymore
Replace all occurrences of the "value" prop with "checked".

Before:
```html
<mt-switch value="true" />
```

After:
```html
<mt-switch checked="true" />
```

### "mt-switch" has no "ghostValue" prop anymore
Remove all occurrences of the "ghostValue" prop.

Before:
```html
<mt-switch ghostValue="true" />
```

After:
```html
<mt-switch />
```

### "mt-switch" has no "padded" prop anymore
Remove all occurrences of the "padded" prop. Use CSS styling instead.

Before:
```html
<mt-switch padded="true" />
```

After:
```html
<mt-switch />
```

### "mt-switch" has no "partlyChecked" prop anymore
Remove all occurrences of the "partlyChecked" prop.

Before:
```html
<mt-switch partlyChecked="true" />
```

After:
```html
<mt-switch />
```

### "mt-switch" has no "label" slot anymore
Replace all occurrences of the "label" slot with the "label" prop.

Before:
```html
<mt-switch>
    <template #label>
        Foobar
    </template>
</mt-switch>
```

After:
```html
<mt-switch label="Foobar">
</mt-switch>
```

### "mt-switch" has no "hint" slot anymore
Remove all occurrences of the "hint" slot.

Before:
```html
<mt-switch>
    <template #hint>
        Foobar
    </template>
</mt-switch>
```

After:
```html
<mt-switch>
    <!-- Slot "hint" was removed with no replacement. -->
</mt-switch>
```
## Removal of "sw-number-field":
The old "sw-number-field" component will be removed in the next major version. Please use the new "mt-number-field" component instead.

We will provide you with a codemod (ESLint rule) to automatically convert your codebase to use the new "mt-number-field" component.

If you don't want to use the codemod, you can manually replace all occurrences of "sw-number-field" with "mt-number-field".

Following changes are necessary:

### "sw-number-field" is removed
Replace all component names from "sw-number-field" with "mt-number-field"

Before:
```html
<sw-number-field />
```
After:
```html
<mt-number-field />
```

### "mt-number-field" has no property "value" anymore
Replace all occurrences of the "value" prop with "modelValue"

Before:
```html
<mt-number-field :value="5" />
```
After:
```html
<mt-number-field :modelValue="5" />
```

### "mt-number-field" v-model:value is deprecated
Replace all occurrences of the "v-model:value" directive with the combination of `:modelValue` and `@change`

Before:
```html
<mt-number-field v-model:value="myValue" />
```
After:
```html
<mt-number-field :modelValue="myValue" @change="myValue = $event" />
```

### "mt-number-field" label slot is deprecated
Replace all occurrences of the "label" slot with the "label" prop

Before:
```html
<mt-number-field>
    <template #label>
        My Label
    </template>
</mt-number-field>
```

After:
```html
<mt-number-field label="My Label" />
```

### "mt-number-field" update:value event is deprecated
Replace all occurrences of the "update:value" event with the "change" event

Before:
```html
<mt-number-field @update:value="updateValue" />
```
After:
```html
<mt-number-field @change="updateValue" />
```
## Removal of "sw-loader":
The old "sw-loader" component will be removed in the next major version. Please use the new "mt-loader" component instead.

We will provide you with a codemod (ESLint rule) to automatically convert your codebase to use the new "mt-loader" component.

If you don't want to use the codemod, you can manually replace all occurrences of "sw-loader" with "mt-loader".

Following changes are necessary:

### "sw-loader" is removed
Replace all component names from "sw-loader" with "mt-loader"

Before:
```html
<sw-loader />
```
After:
```html
<mt-loader />
```
## Removal of "sw-checkbox-field":
The old "sw-checkbox-field" component will be removed in the next major version. Please use the new "mt-checkbox" component instead.

We will provide you with a codemod (ESLint rule) to automatically convert your codebase to use the new "mt-checkbox" component.

If you don't want to use the codemod, you can manually replace all occurrences of "sw-checkbox-field" with "mt-checkbox".

Following changes are necessary:

### "sw-checkbox-field" is removed
Replace all component names from "sw-checkbox-field" with "mt-checkbox"

Before:
```html
<sw-checkbox-field />
```
After:
```html
<mt-checkbox />
```

### "mt-checkbox" has no property "value" anymore
Replace all occurrences of the "value" prop with "checked"

Before:
```html
<sw-checkbox-field :value="myValue" />
```
After:
```html
<mt-checkbox :checked="myValue" />
```

### "mt-checkbox" has changed the v-model usage
Replace all occurrences of the "v-model" directive with "v-model:checked"

Before:
```html
<sw-checkbox-field v-model="isCheckedValue" />
```
After:
```html
<mt-checkbox v-model:checked="isCheckedValue" />
```

### "mt-checkbox" has changed the slot "label" usage
Replace all occurrences of the "label" slot with the "label" prop

Before:
```html
<sw-checkbox-field>
    <template #label>
        Hello Shopware
    </template>
</sw-checkbox-field>
```

After:
```html
<mt-checkbox label="Hello Shopware">
</mt-checkbox>
```

### "mt-checkbox" has removed the slot "hint"
The "hint" slot was removed without replacement

Before:
```html
<sw-checkbox-field>
    <template v-slot:hint>
        Hello Shopware
    </template>
</sw-checkbox-field>
```

### "mt-checkbox" has removed the property "id"
The "id" prop was removed without replacement

Before:
```html
<sw-checkbox-field id="checkbox-id" />
```

### "mt-checkbox" has removed the property "ghostValue"
The "ghostValue" prop was removed without replacement

Before:
```html
<sw-checkbox-field ghostValue="yes" />
```

### "mt-checkbox" has changed the property "partlyChecked"
Replace all occurrences of the "partlyChecked" prop with "partial"

Before:
```html
<sw-checkbox-field partlyChecked />
```
After:
```html
<mt-checkbox partial />
```

### "mt-checkbox" has removed the property "padded"
The "padded" prop was removed without replacement

Before:
```html
<sw-checkbox-field padded />
```

### "mt-checkbox" has changed the event "update:value"
Replace all occurrences of the "update:value" event with "update:checked"

Before:
```html
<sw-checkbox-field @update:value="updateValue" />
```
After:
```html
<mt-checkbox @update:checked="updateValue" />
```
</details>
</details>

# Storefront
We made some changes in the Storefront, which might affect your plugins and themes.
<details>
  <summary>Detailed Changes</summary>

## Removals due to the introduction of ESI for header and footer
* The properties `header` and `footer` and their getter and setter Methods in `\Shopware\Storefront\Framework\Twig\ErrorTemplateStruct` were removed.
* The loading of header, footer, payment methods and shipping methods in `\Shopware\Storefront\Page\GenericPageLoader` is removed.
  Extend `\Shopware\Storefront\Pagelet\Header\HeaderPageletLoader` or `\Shopware\Storefront\Pagelet\Footer\FooterPageletLoader` instead.
* The properties `header`, `footer`, `salesChannelShippingMethods` and `salesChannelPaymentMethods` and their getter and setter Methods in `\Shopware\Storefront\Page\Page` were removed.
  Extend `\Shopware\Storefront\Pagelet\Header\HeaderPagelet` or `\Shopware\Storefront\Pagelet\Footer\FooterPagelet` instead.
* The property `serviceMenu` and its getter and setter Methods in `\Shopware\Storefront\Pagelet\Header\HeaderPagelet` were removed.
  Extend it via the `\Shopware\Storefront\Pagelet\Footer\FooterPagelet` instead.
* The `navigationId` request parameter in `\Shopware\Storefront\Pagelet\Header\HeaderPageletLoader::load` was removed.
* The `setNavigation` method in `\Shopware\Storefront\Pagelet\Menu\Offcanvas\MenuOffcanvasPagelet` was removed.
* The option `tiggerEvent` in `OffcanvasMenuPlugin` JavaScript plugin was removed, use `triggerEvent` instead.
* The following blocks were moved from `src/Storefront/Resources/views/storefront/base.html.twig` to `src/Storefront/Resources/views/storefront/layout/header.html.twig`.
  * `base_header`
  * `base_header_inner`
  * `base_navigation`
  * `base_navigation_inner`
  * `base_offcanvas_navigation`
  * `base_offcanvas_navigation_inner`
* The following blocks were moved from `src/Storefront/Resources/views/storefront/base.html.twig` to `src/Storefront/Resources/views/storefront/layout/footer.html.twig`.
  * `base_footer`
  * `base_footer_inner`
* The template variable `page` in following templates was removed. Provide `header` or `footer` directly.
  * `src/Storefront/Resources/views/storefront/layout/footer/footer.html.twig`
  * `src/Storefront/Resources/views/storefront/layout/header/actions/currency-widget.html.twig`
  * `src/Storefront/Resources/views/storefront/layout/header/actions/language-widget.html.twig`
  * `src/Storefront/Resources/views/storefront/layout/header/top-bar.html.twig`
  * `src/Storefront/Resources/views/storefront/layout/navbar/navbar.html.twig`
* The template variables `activeId` and `activePath` in `src/Storefront/Resources/views/storefront/layout/navbar/categories.html.twig` were removed.
* The template variable `activePath` in `src/Storefront/Resources/views/storefront/layout/navbar/navbar.html.twig` was removed.
* The parameter `activeResult` of `src/Storefront/Resources/views/storefront/layout/sidebar/category-navigation.html.twig` was removed.

## ThemeFileImporterInterface & ThemeFileImporter Removal
Both `\Shopware\Storefront\Theme\ThemeFileImporterInterface` & `\Shopware\Storefront\Theme\ThemeFileImporter` are removed without replacement. These classes are already not used as of v6.6.5.0 and therefore this extension point is removed with no planned replacement.

`getBasePath` & `setBasePath` methods and `basePath` property on `StorefrontPluginConfiguration` are removed. If you need to get the absolute path you should ask for a filesystem instance via `\Shopware\Storefront\Theme\ThemeFilesystemResolver::getFilesystemForStorefrontConfig()` passing in the config object.
This filesystem instance can read files via a relative path and also return the absolute path of a file. Eg:

```php
$fs = $this->themeFilesystemResolver->getFilesystemForStorefrontConfig($storefrontPluginConfig);
foreach($storefrontPluginConfig->getAssetPaths() as $relativePath) {
    $absolutePath = $fs->path('Resources', $relativePath);
}
```

## Removal of deprecated product review loading logic in Storefront
* The service `\Shopware\Storefront\Page\Product\Review\ProductReviewLoader` was removed. Use `\Shopware\Core\Content\Product\SalesChannel\Review\AbstractProductReviewLoader` instead.
* The event `\Shopware\Storefront\Page\Product\Review\ProductReviewsLoadedEvent` was removed. Use `\Shopware\Core\Content\Product\SalesChannel\Review\Event\ProductReviewsLoadedEvent` instead.
* The hook `\Shopware\Storefront\Page\Product\Review\ProductReviewsWidgetLoadedHook` was removed. Use `\Shopware\Core\Content\Product\SalesChannel\Review\ProductReviewsWidgetLoadedHook` instead.
* The struct `\Shopware\Storefront\Page\Product\Review\ReviewLoaderResult` was removed. Use `\Shopware\Core\Content\Product\SalesChannel\Review\ProductReviewResult` instead.

## Removal of Storefront `sw-skin-alert` SCSS mixin
The mixin `sw-skin-alert` will be removed in v6.7.0. Instead of styling the alert manually with CSS selectors and the custom mixin `sw-skin-alert`,
we modify the appearance inside the `alert-*` modifier classes directly with the Bootstrap CSS variables like it is documented: https://getbootstrap.com/docs/5.3/components/alerts/#sass-loops

Before:
```scss
@each $color, $value in $theme-colors {
  .alert-#{$color} {
    @include sw-skin-alert($value, $white);
  }
}
```

After:
```scss
@each $state, $value in $theme-colors {
  .alert-#{$state} {
    --#{$prefix}alert-border-color: #{$value};
    --#{$prefix}alert-bg: #{$white};
    --#{$prefix}alert-color: #{$body-color};
  }
}
```

## Removal of Storefront alert class `alert-has-icon` styling
When rendering an alert using the include template `Resources/views/storefront/utilities/alert.html.twig`, the class `alert-has-icon` will be removed. Helper classes `d-flex align-items-center` will be used instead.

```diff
- <div class="alert alert-info alert-has-icon">
+ <div class="alert alert-info d-flex align-items-center">
    {% sw_icon 'info' %}
    <div class="alert-content-container">
        An important info
    </div>
</div>
```

## Removal of Storefront alert inner container `alert-content`
As of v6.7.0, the superfluous inner container `alert-content` will be removed to have lesser elements and be more aligned with Bootstraps alert structure.
When rendering an alert using the include template `Resources/views/storefront/utilities/alert.html.twig`, the inner container `alert-content` will no longer be present in the HTML output.

The general usage of `Resources/views/storefront/utilities/alert.html.twig` and all include parameters remain the same.

Before:
```html
<div role="alert" class="alert alert-info d-flex align-items-center">
    <span class="icon icon-info"><svg></svg></span>                                                    
    <div class="alert-content-container">
        <div class="alert-content">                                                    
            Your shopping cart is empty.
        </div>                
    </div>
</div>
```

After:
```html
<div role="alert" class="alert alert-info d-flex align-items-center">
    <span class="icon icon-info"><svg></svg></span>                                                    
    <div class="alert-content-container">
        Your shopping cart is empty.
    </div>
</div>
```

## Update polyfills and browser-support

With v6.7.0, the supported browsers in the `.browserslist` file will be updated to `defaults`. This is a recommended setting including browsers with `>0.5%` global usage statistic.
This saves JS bundle size because polyfills for older browser like Chrome 60 or Firefox 60 are no longer included and the native implementation can be used instead.

* [v6.7.0 - Updated browser support](https://browsersl.ist/#q=defaults)
* [v6.6.x - Previous browser support](https://browsersl.ist/#q=%3E%3D+0.5%25%0Alast+2+major+versions%0Anot+dead%0AChrome+%3E%3D+60%0AFirefox+%3E%3D+60%0AFirefox+ESR%0AiOS+%3E%3D+12%0ASafari+%3E%3D+12%0Anot+Explorer+%3C%3D+11)

If you want to restore the previous browser support in your project or want to adjust it, you can use environment variable `BROWSERSLIST` in your `.env`.

```dotenv
# Adjust .browserslist for JS build process
BROWSERSLIST='>= 0.5%, last 2 major versions, not dead, Chrome >= 60, Firefox >= 60, Firefox ESR, iOS >= 12, Safari >= 12, not Explorer <= 11'
```

## Major upgrades of NPM packages

With v6.7.0 we upgrade the following NPM packages to their newest major version.
The upgrades are done for packages related to the webpack JS-build process and do not require changes to the source code.
If you are customizing the webpack config inside `<plugin root>/src/Resources/app/storefront/build/webpack.config.js`, please consolidate the changelogs of the affected packages.

* Upgrade `copy-webpack-plugin` from `11.0.0` to `12.0.2`
* Upgrade `css-loader` from `6.8.1` to `7.1.2`
* Upgrade `postcss-loader` from `7.3.4` to `8.1.1`
* Upgrade `sass-loader` from `13.3.3` to `16.0.4`
* Upgrade `style-loader` from `3.3.3` to `4.0.0`
* Upgrade `webpack-cli` from `5.1.4` to `6.0.1`
* Upgrade `webpack-merge` from `5.10.0` to `6.0.1`
* Upgrade `webpackbar` from `6.0.0` to `7.0.0`
* Upgrade `webpack-dev-server` from `4.15.1` to `5.2.0`

## Removal of NPM packages

With v6.7.0 the following NPM packages will be removed.

### Removed NPM package `query-string`. Native `URLSearchParams` is used instead.

**Creating a query string from object:**

Before:
```js
import queryString from 'query-string';

const paramsString = queryString.stringify({ key: 'value', elementId: 'some-id' })
```

After:
```js
const paramsString = new URLSearchParams({ key: 'value', elementId: 'some-id' }).toString();
```

**Creating an object from queryString:**

Before:
```js
import queryString from 'query-string';

const paramsObj = querystring.parse(window.location.search);
```

After:
```js
const paramsObj = Object.fromEntries(new URLSearchParams(window.location.search).entries());
```

## Added new functions and tokens to complete the Twig integration 
New functions: `sw_block`, `sw_source`, `sw_include` and new tokens: `sw_use`, `sw_embed`, `sw_from` and `sw_import`. 

You can find further details on the use on the documentation page [Shopware's twig functions](https://developer.shopware.com/docs/resources/references/storefront-reference/twig-function-reference.html).

</details>

# App System
We made some changes in the app-system, which might affect your apps.
<details>
  <summary>Detailed Changes</summary>

## Payment: payment states
For asynchronous payments, the default payment state `unconfirmed` was used for the `pay` call and `paid` for `finalized`. This is no longer the case. Payment states are no longer set by default.

## Payment: finalize step
The `finalize` step now transmits the `queryParameters` under the object key `requestData` as other payment calls

## Payment: onlyAvailable flag removed from CheckoutGatewayRoute
The `onlyAvailable` flag in the `Shopware\Core\Checkout\Gateway\SalesChannel\CheckoutGatewayRoute` in the request is removed. The route always filters the payment and shipping methods before calling the checkout gateway based on availability.
</details>

# Hosting & Configuration
We made some changes in the configuration and setup, which might affect your project setups.
<details>
  <summary>Detailed Changes</summary>

## XKeys module is now required for Varnish

The XKeys module is now required for Varnish. If you are using Varnish, you need to ensure that the XKeys module is installed and enabled.
Storing the cache tags for varnish inside redis is not possible anymore, as that solution let to serious scaling issues where the redis tag storage become the bottleneck.

For more information take a look inside the [docs](https://developer.shopware.com/docs/guides/hosting/infrastructure/reverse-http-cache.html#configure-varnish).

This means that the following configuration keys are no longer available:
* `shopware.http_cache.reverse_proxy.use_varnish_xkey`
* `shopware.http_cache.reverse_proxy.redis_url`

## Config keys changes due to improved redis connection handling

Next configuration keys are deprecated and will be removed in the next major version:
* `shopware.cache.invalidation.delay_options.dsn`
* `shopware.increment.<increment_name>.config.url`
* `shopware.number_range.redis_url`
* `shopware.number_range.config.dsn`
* `shopware.cart.redis_url`
* `cart.storage.config.dsn`

To prepare for migration:

1.  For all different redis connections (different DSNs) that are used in the project, add a separate record in the `config/packages/shopware.yaml` file under the `shopware` section, as in upgrade section of this document.
2.  Replace deprecated dsn/url keys with corresponding connection names in the configuration files.
* `shopware.cache.invalidation.delay_options.dsn` -> `shopware.cache.invalidation.delay_options.connection`
* `shopware.increment.<increment_name>.config.url` -> `shopware.increment.<increment_name>.config.connection`
* `shopware.number_range.redis_url` -> `shopware.number_range.config.connection`
* `shopware.number_range.config.dsn` -> `shopware.number_range.config.connection`
* `shopware.cart.redis_url` -> `cart.storage.config.connection`
* `cart.storage.config.dsn` -> `cart.storage.config.connection`

## Service bundle needs to be enabled explicitly

The services bundle now needs to be enabled explicitly in your `config/bundles.php`, to do that add the following line:
```diff
$bundles = [
    ...
    Shopware\Elasticsearch\Elasticsearch::class => ['all' => true],
+    Shopware\Core\Service\Service::class => ['all' => true],
];
```

When you use a [symfony flex setup](https://developer.shopware.com/docs/guides/installation/template.html#symfony-flex) it should pick up the change automatically and apply [that change](https://github.com/shopware/recipes/blob/main/shopware/core/6.7/manifest.json#L34) during the shopware update. 

## Search server now provides OpenSearch/Elasticsearch shards and replicas

Previously we had a default configuration of three shards and three replicas. With 6.7 we removed this default configuration and now the search server is responsible for providing the correct configuration.
This allows that the indices automatically scale based on your nodes available in the cluster.

You can revert to the old behavior by setting the following configuration in your `config/packages/shopware.yml`:

```yaml
elasticsearch:
    index_settings:
        number_of_shards: 3
        number_of_replicas: 3
```

## Message queue size limit

Any message queue message bigger than 256KB will be now rejected by default.
To reduce the size of your messages you should only store the ID of an entity in the message and fetch it later in the message handler.
This can be disabled again with:

```yaml
shopware:
    messenger:
        enforce_message_size: false

```

## Fine-grained caching is removed

The fine-grained caching mechanism for system-config, snippets and theme config was removed, therefore the following configuration settings are no longer available:
* `shopware.cache.tagging.each_config`
* `shopware.cache.tagging.each_snippet`
* `shopware.cache.tagging.each_theme_config`

## `SQL_SET_DEFAULT_SESSION_VARIABLE` has no effect anymore

Removed `SQL_SET_DEFAULT_SESSION_VARIABLES` env variable. It has no effect anymore. 
The previously optional performance tweaks to MySQL are now enforced on connection buildup inside the `\Shopware\Core\Framework\Adapter\Database\MySQLFactory`.

</details>
