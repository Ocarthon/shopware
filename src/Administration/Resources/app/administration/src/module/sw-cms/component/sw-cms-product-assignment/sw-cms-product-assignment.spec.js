/**
 * @sw-package discovery
 */
import { mount } from '@vue/test-utils';

function createEntityCollection(entities = []) {
    return new Shopware.Data.EntityCollection('collection', 'collection', {}, null, entities);
}

async function createWrapper() {
    return mount(
        await wrapTestComponent('sw-cms-product-assignment', {
            sync: true,
        }),
        {
            global: {
                stubs: {
                    'sw-select-base': {
                        template: '<div class="sw-select-base"></div>',
                        props: ['disabled'],
                    },
                    'sw-data-grid': {
                        template: '<div><slot name="actions"></slot></div>',
                        props: ['disabled'],
                    },
                    'sw-context-menu-item': {
                        template: '<div class="sw-context-menu-item"></div>',
                        props: ['disabled'],
                    },
                    'mt-card': {
                        template: '<div class="mt-card"><slot /><slot name="grid"></slot></div>',
                    },
                    'sw-select-result-list': true,
                    'sw-select-result': true,
                    'sw-highlight-text': true,
                    'sw-pagination': true,
                },
                provide: {
                    repositoryFactory: {},
                },
            },
            props: {
                columns: [],
                entityCollection: createEntityCollection(),
                localMode: true,
            },
        },
    );
}

describe('module/sw-cms/component/sw-cms-product-assignment', () => {
    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should have an enabled sw-select-base', async () => {
        const wrapper = await createWrapper();

        const selectBase = wrapper.findComponent('.sw-select-base');

        expect(selectBase.props('disabled')).toBe(false);
    });

    it('should have an disabled sw-select-base', async () => {
        const wrapper = await createWrapper();

        await wrapper.setProps({ disabled: true });

        const selectBase = wrapper.findComponent('.sw-select-base');

        expect(selectBase.props('disabled')).toBe(true);
    });

    it('should have an sw-data-grid', async () => {
        const wrapper = await createWrapper();

        const dataGrid = wrapper.find('.sw-cms-product-assignment__grid');

        expect(dataGrid.exists()).toBe(true);
    });

    it('should have an enabled context menu item', async () => {
        const wrapper = await createWrapper();

        const selectBase = wrapper.findComponent('.sw-context-menu-item');

        expect(selectBase.props('disabled')).toBe(false);
    });

    it('should have an disabled context menu item', async () => {
        const wrapper = await createWrapper();

        await wrapper.setProps({ disabled: true });

        const selectBase = wrapper.findComponent('.sw-context-menu-item');

        expect(selectBase.props('disabled')).toBe(true);
    });
});
