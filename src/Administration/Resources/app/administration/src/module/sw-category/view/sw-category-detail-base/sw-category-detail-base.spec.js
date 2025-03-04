/**
 * @sw-package discovery
 */
import { mount } from '@vue/test-utils';

const categoryMock = {
    media: [],
    name: 'Computer parts',
    footerSalesChannels: [],
    navigationSalesChannels: [],
    serviceSalesChannels: [],
    productAssignmentType: 'product',
    isNew: () => false,
};

async function createWrapper() {
    Shopware.Store.get('swCategoryDetail').$reset();
    Shopware.Store.get('swCategoryDetail').category = categoryMock;

    return mount(await wrapTestComponent('sw-category-detail-base', { sync: true }), {
        global: {
            stubs: {
                'mt-card': {
                    template: '<div class="mt-card"><slot></slot></div>',
                },
                'sw-container': {
                    template: '<div class="sw-container"><slot></slot></div>',
                },
                'sw-single-select': {
                    template: '<input type="select" class="sw-single-select"></input>',
                    props: ['disabled'],
                },
                'sw-entity-tag-select': {
                    template: '<input type="select" class="sw-entity-tag-select"></input>',
                    props: ['disabled'],
                },
                'sw-category-detail-menu': {
                    template: '<div class="sw-category-detail-menu"></div>',
                },
                'sw-category-entry-point-card': true,
                'sw-category-link-settings': true,
                'sw-custom-field-set-renderer': true,
            },
        },
        props: {
            isLoading: false,
            manualAssignedProductsCount: 0,
        },
    });
}

describe('module/sw-category/view/sw-category-detail-base.spec', () => {
    it('should disable all interactive elements', async () => {
        global.activeAclRoles = [];

        const wrapper = await createWrapper();

        wrapper.findAllComponents('input').forEach((element) => {
            expect(element.props('disabled')).toBe(true);
        });
    });

    it('should enable all interactive elements', async () => {
        global.activeAclRoles = ['category.editor'];

        const wrapper = await createWrapper();

        wrapper.findAllComponents('input').forEach((element) => {
            expect(element.props('disabled')).toBe(false);
        });
    });
});
