/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './edit';
import './editor.css';

/**
 * Register the block
 */
registerBlockType(metadata.name, {
    ...metadata,
    edit: Edit,
    // Dynamic block, so save returns null
    save: () => null,
});
