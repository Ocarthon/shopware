/**
 * @sw-package fundamentals@framework
 */

import { mount } from '@vue/test-utils';

async function createWrapper(privileges = []) {
    return mount(
        await wrapTestComponent('sw-settings-currency-list', {
            sync: true,
        }),
        {
            global: {
                mocks: {
                    $route: {
                        query: {
                            page: 1,
                            limit: 25,
                        },
                    },
                },
                provide: {
                    repositoryFactory: {
                        create: () => ({
                            search: () => {
                                return Promise.resolve([
                                    {
                                        id: '1a2b3c',
                                        name: 'Test currency',
                                        isoCode: 'TES',
                                        shortName: 'test',
                                        symbol: 'TES',
                                        factor: 1,
                                        decimalPrecision: 1,
                                    },
                                ]);
                            },
                        }),
                    },
                    acl: {
                        can: (identifier) => {
                            if (!identifier) {
                                return true;
                            }

                            return privileges.includes(identifier);
                        },
                    },
                    searchRankingService: {},
                },
                stubs: {
                    'sw-page': {
                        template: `
    <div class="sw-page">
        <slot name="smart-bar-actions"></slot>
        <slot name="content">CONTENT</slot>
        <slot></slot>
    </div>`,
                    },
                    'sw-icon': true,
                    'sw-search-bar': true,
                    'sw-entity-listing': {
                        props: ['items'],
                        template: `
<div>
    <template v-for="item in items">
        <slot name="actions" v-bind="{ item }"></slot>
    </template>
</div>
                `,
                    },
                    'sw-language-switch': true,
                    'sw-context-menu-item': true,
                },
            },
        },
    );
}

describe('module/sw-settings-currency/page/sw-settings-currency-list', () => {
    it('should be a Vue.JS component', async () => {
        const wrapper = await createWrapper();
        await wrapper.vm.$nextTick();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should not be able to create a new currency', async () => {
        const wrapper = await createWrapper();
        await wrapper.vm.$nextTick();

        const createButton = wrapper.find('.sw-settings-currency-list__button-create');

        expect(createButton.attributes('disabled')).toBeDefined();
    });

    it('should be able to create a new currency', async () => {
        const wrapper = await createWrapper([
            'currencies.creator',
        ]);
        await wrapper.vm.$nextTick();

        const createButton = wrapper.find('.sw-settings-currency-list__button-create');

        expect(createButton.attributes().disabled).toBeFalsy();
    });

    it('should not be able to inline edit', async () => {
        const wrapper = await createWrapper();
        await wrapper.vm.$nextTick();

        const entityListing = wrapper.find('.sw-settings-currency-list-grid');
        expect(entityListing.exists()).toBeTruthy();
        expect(entityListing.attributes()['allow-inline-edit']).toBeFalsy();
    });

    it('should be able to inline edit', async () => {
        const wrapper = await createWrapper([
            'currencies.editor',
        ]);
        await wrapper.vm.$nextTick();

        const entityListing = wrapper.find('.sw-settings-currency-list-grid');
        expect(entityListing.exists()).toBeTruthy();
        expect(entityListing.attributes()['allow-inline-edit']).toBeTruthy();
    });

    it('should not be able to delete', async () => {
        const wrapper = await createWrapper();
        await wrapper.vm.$nextTick();

        const deleteMenuItem = wrapper.find('.sw-currency-list__delete-action');
        expect(deleteMenuItem.attributes().disabled).toBeTruthy();
    });

    it('should be able to delete', async () => {
        const wrapper = await createWrapper([
            'currencies.deleter',
        ]);
        await wrapper.vm.$nextTick();

        const deleteMenuItem = wrapper.find('.sw-currency-list__delete-action');
        expect(deleteMenuItem.attributes().disabled).toBeFalsy();
    });

    it('should not be able to edit', async () => {
        const wrapper = await createWrapper();
        await wrapper.vm.$nextTick();

        const editMenuItem = wrapper.find('.sw-currency-list__edit-action');
        expect(editMenuItem.attributes().disabled).toBeTruthy();
    });

    it('should be able to edit', async () => {
        const wrapper = await createWrapper([
            'currencies.editor',
        ]);
        await wrapper.vm.$nextTick();

        const editMenuItem = wrapper.find('.sw-currency-list__edit-action');
        expect(editMenuItem.attributes().disabled).toBeFalsy();
    });
});
