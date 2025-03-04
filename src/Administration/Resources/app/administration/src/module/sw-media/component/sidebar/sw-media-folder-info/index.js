import template from './sw-media-folder-info.html.twig';
import './sw-media-folder-info.scss';

const { Component, Mixin, Context } = Shopware;
const { mapPropertyErrors } = Component.getComponentHelper();

/**
 * @sw-package discovery
 */
// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    inject: [
        'repositoryFactory',
        'acl',
    ],

    emits: ['media-folder-renamed'],

    mixins: [
        Mixin.getByName('media-sidebar-modal-mixin'),
    ],

    props: {
        mediaFolder: {
            type: Object,
            required: true,
            validator(value) {
                return value.getEntityName() === 'media_folder';
            },
        },

        editable: {
            type: Boolean,
            required: false,
            default: false,
        },
    },

    computed: {
        mediaFolderRepository() {
            return this.repositoryFactory.create('media_folder');
        },

        createdAt() {
            return Shopware.Utils.format.date(this.mediaFolder.createdAt);
        },

        ...mapPropertyErrors('mediaFolder', ['name']),

        nameItemClasses() {
            return {
                'has--error': !!this.mediaFolderNameError,
            };
        },
    },

    methods: {
        async onChangeFolderName(newName) {
            this.mediaFolder.name = newName;
            await this.mediaFolderRepository.save(this.mediaFolder, Context.api);
            this.$emit('media-folder-renamed');
        },

        quickActionClasses(disabled) {
            return [
                'sw-media-sidebar__quickaction',
                {
                    'sw-media-sidebar__quickaction--disabled': disabled,
                },
            ];
        },
    },
};
