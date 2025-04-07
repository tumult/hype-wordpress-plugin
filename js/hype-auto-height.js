/**
 * Tumult Hype Auto Height functionality
 * Based on HypeAutoHeight.js by Max Ziebell
 * https://github.com/worldoptimizer/HypeAutoHeight
 *
 * This script automatically adjusts the height of Hype animations based on their aspect ratio
 * to solve issues with responsive layouts and 100% height animations.
 */

(function() {
    /**
     * Handle auto height adjustment for a Hype document
     *
     * @param {HypeDocument} hypeDocument - The Hype document instance
     * @param {HTMLElement} element - The document element
     * @param {Object} event - The event object
     */
    function handleHypeAutoHeight(hypeDocument, element, event) {
        // Find the container element (either provided or parent of the document element)
        var container = element.closest('.hype-auto-height');
        if (!container) return;
        
        // Store original dimensions
        var originalWidth = hypeDocument.documentWidth();
        var originalHeight = hypeDocument.documentHeight();
        var aspectRatio = originalHeight / originalWidth;
        
        /**
         * Update the height of the container based on its width and the aspect ratio
         */
        function updateHeight() {
            if (!container) return;
            var currentWidth = container.offsetWidth;
            var calculatedHeight = currentWidth * aspectRatio;
            container.style.height = calculatedHeight + "px";
        }
        
        // Initial height setting
        updateHeight();
        
        // Update on window resize
        window.addEventListener("resize", updateHeight);
        
        // Custom event for layout changes within Hype
        hypeDocument.continueAfterSceneTransition = function() {
            updateHeight();
            return true;
        };
        
        // For Hype documents with multiple scenes that might have different aspect ratios
        hypeDocument.addEventListener("HypeSceneLoad", function() {
            originalWidth = hypeDocument.documentWidth();
            originalHeight = hypeDocument.documentHeight();
            aspectRatio = originalHeight / originalWidth;
            updateHeight();
        });
    }
    
    // Register the function to be called when Hype documents load
    if ("HYPE" in window) {
        for (var documentName in HYPE.documents) {
            var hypeDoc = HYPE.documents[documentName];
            if (hypeDoc && hypeDoc.documentElement()) {
                var container = hypeDoc.documentElement().closest('.hype-auto-height');
                if (container) {
                    handleHypeAutoHeight(hypeDoc, hypeDoc.documentElement(), {});
                }
            }
        }
    }
    
    // For future documents that will load
    if (window.HYPE_eventListeners === undefined) {
        window.HYPE_eventListeners = Array();
    }
    
    window.HYPE_eventListeners.push({
        type: "HypeDocumentLoad",
        callback: function(hypeDocument, element, event) {
            var container = element.closest('.hype-auto-height');
            if (container) {
                handleHypeAutoHeight(hypeDocument, element, event);
            }
        }
    });
})();