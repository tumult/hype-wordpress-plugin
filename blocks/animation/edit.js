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
import { useRef, useEffect } from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';

/**
 * The edit function for the Tumult Hype Animation block.
 *
 * @param {Object} props               Block props.
 * @param {Object} props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to set attributes.
 * @return {WPElement} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
    const { animationId, width, height, isResponsive, autoHeight } = attributes;
    const blockProps = useBlockProps();
    const frameRef = useRef(null);
    
    // Get animations from localized data
    const animations = window.hypeAnimationsData?.animations || [];
    const defaultImage = window.hypeAnimationsData?.defaultImage || '';
    
    // Debug log to check animations data
    useEffect(() => {
        console.log("Hype Animations Data:", window.hypeAnimationsData);
        console.log("Available animations:", animations);
    }, []);
    
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
        console.log("Animation selected:", newAnimationId);
        setAttributes({ 
            animationId: parseInt(newAnimationId) 
        });
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
    
    // Enhanced animation selector
    const AnimationSelector = () => {
        if (animations.length === 0) {
            return (
                <div className="hype-animation-selector">
                    <p>{__('No animations found. Please upload animations first.', 'tumult-hype-animations')}</p>
                </div>
            );
        }
        
        return (
            <div className="hype-animation-selector">
                <SelectControl
                    value={animationId}
                    options={animationOptions}
                    onChange={onChangeAnimation}
                />
            </div>
        );
    };
    
    // No controls for the animation
    
    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Animation Settings', 'tumult-hype-animations')}>
                    <AnimationSelector />
                    
                    <TextControl
                        label={__('Width', 'tumult-hype-animations')}
                        value={width}
                        onChange={(value) => setAttributes({ width: value })}
                        help={__('Enter a value in px or %', 'tumult-hype-animations')}
                    />
                    
                    <TextControl
                        label={__('Height', 'tumult-hype-animations')}
                        value={height}
                        onChange={(value) => setAttributes({ height: value })}
                        help={__('Enter a value in px or %', 'tumult-hype-animations')}
                    />
                    
                    <ToggleControl
                        label={__('Responsive', 'tumult-hype-animations')}
                        checked={isResponsive}
                        onChange={(value) => setAttributes({ isResponsive: value })}
                        help={__('Scale animation to fit container', 'tumult-hype-animations')}
                    />

                    <ToggleControl
                        label={__('Auto Height', 'tumult-hype-animations')}
                        checked={autoHeight}
                        onChange={(value) => setAttributes({ autoHeight: value })}
                        help={__('Automatically adjust height based on content aspect ratio', 'tumult-hype-animations')}
                    />
                </PanelBody>
            </InspectorControls>
            
            <div {...blockProps}>
                {animationId > 0 ? (
                    <div className="hype-animation-preview">
                        <div className="hype-animation-preview-header">
                            <span className="dashicons dashicons-format-video"></span>
                            <h3>{__('Tumult Hype Animation', 'tumult-hype-animations')}</h3>
                            <h2 className="hype-animation-title">
                                {animations.find(a => a.id === animationId)?.name || __('Animation ID: ', 'tumult-hype-animations') + animationId}
                            </h2>
                            <p className="hype-animation-dimensions">
                                {width || '100%'} × {height || 'auto'}
                                {isResponsive ? ` (${__('Responsive', 'tumult-hype-animations')})` : ''}
                                {autoHeight ? ` (${__('Auto Height', 'tumult-hype-animations')})` : ''}
                            </p>
                        </div>
                        {/* Add the thumbnail preview */}
                        <div className="hype-animation-thumbnail-preview">
                            <img 
                                src={animations.find(a => a.id === animationId)?.thumbnail || defaultImage}
                                alt={animations.find(a => a.id === animationId)?.name || __('Animation Preview', 'tumult-hype-animations')}
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