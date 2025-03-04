import template from './sw-cms-preview-cross-selling.html.twig';
import './sw-cms-preview-cross-selling.scss';

/**
 * @private
 * @sw-package discovery
 */
export default {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },
};
