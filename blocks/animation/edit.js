/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
    InspectorControls,
    useBlockProps,
} from '@wordpress/block-editor';
import {
    PanelBody,
    SelectControl,
    TextControl,
    ToggleControl,
    Placeholder,
    Button,
} from '@wordpress/components';
import { useRef, useEffect, useState, RawHTML } from '@wordpress/element';
import { escapeHTML, escapeAttribute } from '@wordpress/escape-html';

/**
 * Validates a dimension value (width, height, minHeight).
 * Accepts: px, %, vh, vw, em, rem, cm, mm, in, pt, pc
 *
 * @param {string} value The dimension value to validate.
 * @return {Object} Object with isValid and error message.
 */
const validateDimension = (value) => {
    if (!value) {
        return { isValid: true, error: '' };
    }
    
    // Check if it matches valid dimension patterns
    const validPattern = /^(\d+(\.\d+)?)(px|%|vh|vw|em|rem|cm|mm|in|pt|pc)$/i;
    if (!validPattern.test(value.trim())) {
        return {
            isValid: false,
            error: __('Invalid format. Use number + unit (e.g., 300px, 100%, 50vh)', 'tumult-hype-animations')
        };
    }
    
    const numericValue = parseFloat(value);
    if (isNaN(numericValue)) {
        return {
            isValid: false,
            error: __('Numeric value must be a valid number', 'tumult-hype-animations')
        };
    }
    
    if (numericValue <= 0) {
        return {
            isValid: false,
            error: __('Value must be greater than 0', 'tumult-hype-animations')
        };
    }
    
    // Warn about excessively large values
    if (numericValue > 5000) {
        return {
            isValid: true,
            error: __('⚠️ Very large value (>5000). Consider using smaller dimensions.', 'tumult-hype-animations')
        };
    }
    
    return { isValid: true, error: '' };
};

/**
 * The edit function for the Tumult Hype Animation block.
 *
 * @param {Object} props               Block props.
 * @param {Object} props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to set attributes.
 * @return {WPElement} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
    const { animationId, width, height, autoHeight, embedMode, minHeight } = attributes;
    const blockProps = useBlockProps();
    const frameRef = useRef(null);
    
    // Get animations from localized data
    const animations = window.hypeAnimationsData?.animations || [];
    const defaultImage = window.hypeAnimationsData?.defaultImage || '';
    const getAnimationById = (id) => animations.find((anim) => Number(anim.id) === Number(id));
    const [widthError, setWidthError] = useState('');
    const [heightError, setHeightError] = useState('');
    const [minHeightError, setMinHeightError] = useState('');
    const getDefaultMinHeight = (animation) => {
        if (!animation) {
            return '';
        }
        if (animation.defaultMinHeight) {
            return animation.defaultMinHeight;
        }
        if (animation.originalHeight && /px$/i.test(animation.originalHeight)) {
            return animation.originalHeight;
        }
        return '';
    };
    const getSafeAnimationHTML = (animation) => escapeHTML(animation?.name || '');
    const getSafeAnimationAttr = (animation, fallback) => escapeAttribute(animation?.name || fallback);
    const selectedAnimation = animationId ? getAnimationById(animationId) : undefined;
    const debugEnabled = Boolean(window.hypeAnimationsData?.debug);
    const debugLog = (...args) => {
        if (debugEnabled && typeof console !== 'undefined') {
            // eslint-disable-next-line no-console
            console.log(...args);
        }
    };
    const debugWarn = (...args) => {
        if (debugEnabled && typeof console !== 'undefined') {
            // eslint-disable-next-line no-console
            console.warn(...args);
        }
    };
    const resolvedTitleHTML = selectedAnimation
        ? getSafeAnimationHTML(selectedAnimation)
        : escapeHTML(__('Animation ID: ', 'tumult-hype-animations') + animationId);
    
    // Debug log to check animations data
    useEffect(() => {
        debugLog('Hype Animations Data:', window.hypeAnimationsData);
        debugLog('Available animations:', animations);

        if (!window.hypeAnimationsData || !window.hypeAnimationsData.animations || animations.length === 0) {
            debugWarn('No animations available or data failed to load. Check WordPress admin for uploaded animations.');
        }
    }, [debugEnabled, animations.length]);
    
    // Create options for SelectControl
    const animationOptions = [
        { value: 0, label: __('Select an animation', 'tumult-hype-animations') }
    ].concat(animations.map(function(animation) {
        return {
            value: animation.id,
            label: animation.name
        };
    }));
    
    // Function to update selected animation
    const onChangeAnimation = (newAnimationId) => {
        debugLog('Animation selected:', newAnimationId);

        const selected = getAnimationById(newAnimationId);
        const parsedId = parseInt(newAnimationId, 10) || 0;
        const nextAttributes = { animationId: parsedId };

        if (selected) {
            if (selected.originalWidth && !width) {
                nextAttributes.width = selected.originalWidth;
            }

            if (selected.originalHeight && !height) {
                nextAttributes.height = selected.originalHeight;
            }

            const shouldEnableAutoHeight = Boolean(
                selected.originalHeight === '100%' || selected.originalHeightUnit === '%' || (selected.originalHeight && selected.originalHeight.indexOf('%') !== -1)
            );

            if (shouldEnableAutoHeight) {
                nextAttributes.autoHeight = true;
            }

            if (!minHeight) {
                const suggestedMinHeight = getDefaultMinHeight(selected);
                if (suggestedMinHeight) {
                    nextAttributes.minHeight = suggestedMinHeight;
                }
            }
        }

        setAttributes(nextAttributes);
    };
    
    // Animation control functions
    const playPause = () => {
        if (frameRef.current && frameRef.current.contentWindow) {
            frameRef.current.contentWindow.postMessage('playPause', '*');
        }
    };
    
    const restart = () => {
        if (frameRef.current && frameRef.current.contentWindow) {
            frameRef.current.contentWindow.postMessage('restart', '*');
        }
    };
    
    // Enhanced animation selector with search/filter
    const AnimationSelector = () => {
        const [search, setSearch] = useState('');
        if (animations.length === 0) {
            return (
                <div className="hype-animation-selector">
                    <p>{__('No animations found. Please upload animations first.', 'tumult-hype-animations')}</p>
                </div>
            );
        }
        // Filter animations by search
        const normalizedSearch = search.toLowerCase();
        const filteredOptions = [
            { value: 0, label: __('Select an animation', 'tumult-hype-animations') },
            ...animations
                .filter(anim => (anim.name || '').toLowerCase().includes(normalizedSearch))
                .map(animation => ({ value: animation.id, label: animation.name }))
        ];
        return (
            <div className="hype-animation-selector">
                <TextControl
                    value={search}
                    onChange={setSearch}
                    placeholder={__('Search animations...', 'tumult-hype-animations')}
                    style={{ marginBottom: 8 }}
                />
                <SelectControl
                    value={animationId}
                    options={filteredOptions}
                    onChange={onChangeAnimation}
                />
            </div>
        );
    };
    
    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Animation Settings', 'tumult-hype-animations')}>
                    <AnimationSelector />
                    <TextControl
                        label={__('Width', 'tumult-hype-animations')}
                        value={width}
                        onChange={(value) => {
                            const validation = validateDimension(value);
                            setWidthError(validation.error);
                            setAttributes({ width: value });
                        }}
                        help={widthError || __('Enter a value like 300px or 100%', 'tumult-hype-animations')}
                        className={widthError && !validateDimension(width).isValid ? 'has-error' : ''}
                        status={widthError && !validateDimension(width).isValid ? 'error' : 'info'}
                    />
                    <TextControl
                        label={__('Height', 'tumult-hype-animations')}
                        value={height}
                        onChange={(value) => {
                            const validation = validateDimension(value);
                            setHeightError(validation.error);
                            setAttributes({ height: value });
                        }}
                        help={heightError || __('Enter a value in px or %', 'tumult-hype-animations')}
                        className={heightError && !validateDimension(height).isValid ? 'has-error' : ''}
                        status={heightError && !validateDimension(height).isValid ? 'error' : 'info'}
                    />
                    <TextControl
                        label={__('Minimum Height', 'tumult-hype-animations')}
                        value={minHeight || ''}
                        onChange={(value) => {
                            const validation = validateDimension(value);
                            setMinHeightError(validation.error);
                            setAttributes({ minHeight: value });
                        }}
                        placeholder={selectedAnimation?.defaultMinHeight || selectedAnimation?.originalHeight || '480px'}
                        help={minHeightError || __('Optional fallback height for responsive layouts. Accepts values like 400px, 60vh, or 75%. Leave empty to let the animation calculate automatically.', 'tumult-hype-animations')}
                        className={minHeightError && !validateDimension(minHeight).isValid ? 'has-error' : ''}
                        status={minHeightError && !validateDimension(minHeight).isValid ? 'error' : 'info'}
                    />
                    <SelectControl
                        label={__('Embed Mode', 'tumult-hype-animations')}
                        value={embedMode || 'div'}
                        options={[
                            { value: 'div', label: __('Div (Default)', 'tumult-hype-animations') },
                            { value: 'iframe', label: __('Iframe', 'tumult-hype-animations') }
                        ]}
                        onChange={(value) => setAttributes({ embedMode: value })}
                        help={__('Choose how to embed the animation', 'tumult-hype-animations')}
                    />
                    {/* Deep link to dashboard animation management */}
                    <div style={{ margin: '16px 0' }}>
                        <Button
                            href={window?.hypeAnimationsData?.dashboardUrl || '/wp-admin/admin.php?page=hypeanimations_panel'}
                            target="_blank"
                            variant="secondary"
                            icon="admin-generic"
                        >
                            {__('Manage Animations in Dashboard', 'tumult-hype-animations')}
                        </Button>
                    </div>
                    {/* Information panel with original dimensions */}
                    {animationId > 0 && selectedAnimation?.originalWidth && (
                        <div className="hype-animation-info-panel">
                            <h4>{__('Animation Information', 'tumult-hype-animations')}</h4>
                            <p>
                                <strong>{__('Original Width: ', 'tumult-hype-animations')}</strong>
                                {selectedAnimation?.originalWidth}
                            </p>
                            <p>
                                <strong>{__('Original Height: ', 'tumult-hype-animations')}</strong>
                                {selectedAnimation?.originalHeight}
                            </p>
                            {(minHeight || selectedAnimation?.defaultMinHeight) && (
                                <p>
                                    <strong>{__('Minimum Height:', 'tumult-hype-animations')}</strong>{' '}
                                    {escapeHTML(minHeight || selectedAnimation?.defaultMinHeight)}
                                </p>
                            )}
                            {selectedAnimation?.originalHeight === '100%' && (
                                <p className="hype-animation-help-link">
                                    <a href="https://forums.tumult.com/t/tumult-hype-animations-wordpress-plugin/11074" target="_blank">
                                        {__('Need help with 100% height animations?', 'tumult-hype-animations')}
                                    </a>
                                </p>
                            )}
                        </div>
                    )}
                </PanelBody>
            </InspectorControls>
            
            <div {...blockProps}>
                {animationId > 0 ? (
                    <div className="hype-animation-preview">
                        <div className="hype-animation-preview-header">
                            <span className="dashicons dashicons-format-video"></span>
                            <h3>{__('Tumult Hype Animation', 'tumult-hype-animations')}</h3>
                            <h2 className="hype-animation-title">
                                <RawHTML>{resolvedTitleHTML}</RawHTML>
                            </h2>
                            {/* Display the original dimensions if available */}
                            {selectedAnimation?.originalWidth && (
                                <p className="hype-animation-original-dimensions">
                                    {__('Original Size: ', 'tumult-hype-animations')}
                                    {selectedAnimation?.originalWidth} × {selectedAnimation?.originalHeight}
                                </p>
                            )}
                        </div>
                        {/* Add the thumbnail preview */}
                        <div className="hype-animation-thumbnail-preview">
                            <img 
                                src={selectedAnimation?.thumbnail || defaultImage}
                                alt={getSafeAnimationAttr(selectedAnimation, __('Animation Preview', 'tumult-hype-animations'))}
                                className="hype-animation-thumbnail"
                            />
                        </div>
                    </div>
                ) : (
                    <Placeholder
                        icon="format-video"
                        label={__('Tumult Hype Animation', 'tumult-hype-animations')}
                        instructions={__('Select a Tumult Hype animation to insert', 'tumult-hype-animations')}
                    >
                        <AnimationSelector />
                        {animations.length > 0 && (
                            <p style={{ textAlign: 'center', marginTop: '15px' }}>
                                {__('Click on an animation above to insert it', 'tumult-hype-animations')}
                            </p>
                        )}
                    </Placeholder>
                )}
            </div>
        </>
    );
}