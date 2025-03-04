/*
 * @sw-package inventory
 */

import template from './sw-product-detail-layout.html.twig';
import './sw-product-detail-layout.scss';

const { Context, Utils } = Shopware;
const { Criteria } = Shopware.Data;
const { cloneDeep, merge, get } = Utils.object;

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    inject: [
        'repositoryFactory',
        'cmsService',
        'feature',
        'acl',
    ],

    data() {
        return {
            showLayoutModal: false,
            isConfigLoading: false,
        };
    },

    computed: {
        cmsPageRepository() {
            return this.repositoryFactory.create('cms_page');
        },

        cmsPageId() {
            return get(this.product, 'cmsPageId', null);
        },

        showCmsForm() {
            return (!this.isLoading || !this.isConfigLoading) && !this.currentPage.locked;
        },

        product() {
            return Shopware.Store.get('swProductDetail').product;
        },

        isLoading() {
            return Shopware.Store.get('swProductDetail').isLoading;
        },

        cmsPageCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addAssociation('previewMedia');
            criteria.addAssociation('sections');
            criteria.getAssociation('sections').addSorting(Criteria.sort('position'));

            criteria.addAssociation('sections.blocks');
            criteria.getAssociation('sections.blocks').addSorting(Criteria.sort('position', 'ASC')).addAssociation('slots');

            return criteria;
        },

        languageId() {
            return Shopware.Context.api.languageId;
        },

        currentPage() {
            return Shopware.Store.get('cmsPage').currentPage;
        },

        cmsPageState() {
            return Shopware.Store.get('cmsPage');
        },
    },

    watch: {
        cmsPageId() {
            this.cmsPageState.resetCmsPageState();
            this.handleGetCmsPage();
        },

        product: {
            deep: true,
            handler(value) {
                if (!value) {
                    return;
                }

                this.updateCmsPageDataMapping();
            },
        },

        languageId() {
            this.cmsPageState.resetCmsPageState();
            this.handleGetCmsPage();
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            // Keep current layout configuration if page sections exist
            const sections = this.currentPage?.sections ?? [];

            if (sections.length) {
                return;
            }

            this.handleGetCmsPage();
        },

        onOpenLayoutModal() {
            if (!this.acl.can('product.editor')) {
                return;
            }

            this.showLayoutModal = true;
        },

        onCloseLayoutModal() {
            this.showLayoutModal = false;
        },

        onOpenInPageBuilder() {
            if (!this.currentPage) {
                this.$router.push({ name: 'sw.cms.create' });
            } else {
                this.$router.push({
                    name: 'sw.cms.detail',
                    params: { id: this.currentPage.id },
                });
            }
        },

        onSelectLayout(cmsPageId) {
            if (!this.product) {
                return;
            }

            this.product.cmsPageId = cmsPageId;
            this.product.slotConfig = null;
            Shopware.Store.get('swProductDetail').product = this.product;
        },

        handleGetCmsPage() {
            if (!this.cmsPageId) {
                return;
            }

            this.isConfigLoading = true;

            this.cmsPageRepository.get(this.cmsPageId, Context.api, this.cmsPageCriteria).then((cmsPage) => {
                if (this.product.slotConfig && cmsPage) {
                    cmsPage.sections.forEach((section) => {
                        section.blocks.forEach((block) => {
                            block.slots.forEach((slot) => {
                                if (!this.product.slotConfig[slot.id]) {
                                    return;
                                }

                                slot.config = slot.config || {};
                                merge(slot.config, cloneDeep(this.product.slotConfig[slot.id]));
                            });
                        });
                    });
                }

                this.cmsPageState.setCurrentPage(cmsPage);
                this.updateCmsPageDataMapping();
                this.isConfigLoading = false;
            });
        },

        updateCmsPageDataMapping() {
            this.cmsPageState.setCurrentMappingEntity('product');
            this.cmsPageState.setCurrentMappingTypes(this.cmsService.getEntityMappingTypes('product'));
            this.cmsPageState.setCurrentDemoEntity(this.product);
        },

        onResetLayout() {
            this.onSelectLayout(null);
        },

        elementUpdate(element) {
            const slotContent = this.product.slotConfig[element.id]?.content;
            if (slotContent && slotContent.value) {
                slotContent.value = element.config.content.value;
            }
        },
    },
};
