/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';

/**
 * Add block transforms for Tumult Hype animations
 * 
 * This adds the ability to transform from:
 * - Shortcode to block (when explicitly selected)
 * - Block to shortcode
 */
function addHypeAnimationTransforms(settings, name) {
    if (name !== 'tumult-hype-animations/animation') {
        return settings;
    }

    return {
        ...settings,
        transforms: {
            from: [
                {
                    type: 'shortcode',
                    tag: 'hypeanimations_anim',
                    // Setting to lower priority to prevent automatic transformation
                    priority: 20,
                    // Allow the transform when explicitly selected but not automatically
                    isMatch: (attributes) => {
                        // Only match when explicitly chosen via transform UI
                        return true;
                    },
                    // Prevent automatic transform but keep manual transform option
                    __experimentalConvert: (attributes) => {
                        return false;
                    },
                    attributes: {
                        animationId: {
                            type: 'number',
                            shortcode: ({ named: { id } }) => {
                                return parseInt(id) || 0;
                            }
                        },
                        width: {
                            type: 'string',
                            shortcode: ({ named: { width } }) => {
                                return width || '100%';
                            }
                        },
                        height: {
                            type: 'string',
                            shortcode: ({ named: { height } }) => {
                                return height || 'auto';
                            }
                        },
                        isResponsive: {
                            type: 'boolean',
                            shortcode: ({ named: { responsive } }) => {
                                return responsive === '1';
                            }
                        },
                        autoHeight: {
                            type: 'boolean',
                            shortcode: ({ named: { auto_height } }) => {
                                return auto_height === '1';
                            }
                        }
                    }
                },
                {
                    type: 'block',
                    blocks: ['core/shortcode'],
                    isMatch: (attributes) => {
                        // Check if this shortcode block contains a Hype animation shortcode
                        const { text } = attributes;
                        return text && /\[hypeanimations_anim\s+.*?\]/.test(text);
                    },
                    transform: (attributes) => {
                        const { text } = attributes;
                        // Extract shortcode parameters
                        const idMatch = text.match(/id=["']?(\d+)["']?/);
                        const widthMatch = text.match(/width=["']?([^"'\s]+)["']?/);
                        const heightMatch = text.match(/height=["']?([^"'\s]+)["']?/);
                        const responsiveMatch = text.match(/responsive=["']?1["']?/);
                        const autoHeightMatch = text.match(/auto_height=["']?1["']?/);
                        
                        return createBlock('tumult-hype-animations/animation', {
                            animationId: idMatch ? parseInt(idMatch[1]) : 0,
                            width: widthMatch ? widthMatch[1] : '100%',
                            height: heightMatch ? heightMatch[1] : 'auto',
                            isResponsive: !!responsiveMatch,
                            autoHeight: !!autoHeightMatch
                        });
                    }
                }
            ],
            // Add "to shortcode" transform to convert blocks back to shortcodes
            to: [
                {
                    type: 'block',
                    blocks: ['core/shortcode'],
                    transform: ({ animationId, width, height, isResponsive, autoHeight }) => {
                        let shortcodeAttrs = `id="${animationId}"`;
                        
                        if (width) {
                            shortcodeAttrs += ` width="${width}"`;
                        }
                        
                        if (height) {
                            shortcodeAttrs += ` height="${height}"`;
                        }
                        
                        if (isResponsive) {
                            shortcodeAttrs += ` responsive="1"`;
                        }
                        
                        if (autoHeight) {
                            shortcodeAttrs += ` auto_height="1"`;
                        }
                        
                        const shortcode = `[hypeanimations_anim ${shortcodeAttrs}]`;
                        
                        return createBlock('core/shortcode', {
                            text: shortcode,
                        });
                    },
                }
            ]
        }
    };
}

addFilter(
    'blocks.registerBlockType',
    'tumult-hype-animations/transforms',
    addHypeAnimationTransforms
);