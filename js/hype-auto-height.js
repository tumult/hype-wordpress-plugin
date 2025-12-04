/**
 * Tumult Hype Auto Height
 * Based on HypeAutoHeight by Max Ziebell (https://github.com/worldoptimizer/HypeAutoHeight)
 * Original extension licensed under the MIT License. Modifications retain full attribution to Max Ziebell.
 * 
 * This script automatically adjusts the height of Hype animations based on their layout ratios
 * to solve issues with responsive layouts and 100% height animations.
 */

(function() {
    // Debug mode - set to true to enable console logging
    var DEBUG = false;
    
    function log() {
        if (DEBUG && console && console.log) {
            console.log.apply(console, arguments);
        }
    }

    /**
     * Handle auto height adjustment for a Hype document
     * Specifically for documents embedded in divs with class 'hype-auto-height'
     * 
     * @param {Object} hypeDocument The Hype document instance
     * @param {Element} element The Hype container element
     * @param {Object} event The load event
     * @return {boolean} Return value from handler
     */
    function hypeAutoHeightOnDocumentLoad(hypeDocument, element, event) {
        log("HypeDocumentLoad event fired for:", hypeDocument.documentId());
        handleHypeAutoHeight(hypeDocument, element, event);
        return true;
    }

    /**
     * Handle auto height adjustment for a Hype document
     * Specifically for documents embedded in divs with class 'hype-auto-height'
     */
    function handleHypeAutoHeight(hypeDocument, element, event) {
        log("Handling auto height for", hypeDocument.documentId());
        
        // Find the Hype container element
        var hypeContainer = document.getElementById(hypeDocument.documentId());
        if (!hypeContainer) {
            log("Could not find Hype container by ID, trying alternate method");
            hypeContainer = hypeDocument.documentElement();
            
            if (!hypeContainer) {
                log("Could not find Hype container element");
                return;
            }
        }
        
        // Find parent with hype-auto-height class
        var container = hypeContainer.closest('.hype-auto-height');
        if (!container) {
            log("No container with .hype-auto-height class found, exiting");
            return;
        }
        
        log("Found container:", container);
        
        var dataMinHeight = container.getAttribute('data-min-height');
        
        function applyMinHeight(value) {
            if (!value) {
                return;
            }
            container.style.minHeight = value;
            hypeContainer.style.minHeight = value;
        }
        
        applyMinHeight(dataMinHeight);
        
        function updateHeight() {
            // Ensure the container is visible and has dimensions
            if (container.offsetWidth === 0) {
                log("Container width is 0, retrying in 200ms");
                setTimeout(updateHeight, 200);
                return;
            }
            
            // Get current scene and layout information
            var currentScene = hypeDocument.currentSceneName();
            var currentLayout = hypeDocument.currentLayoutName();
            
            // Get all layouts for the current scene
            var layouts = hypeDocument.layoutsForSceneNamed(currentScene);
            if (!layouts || !layouts.length) {
                log("No layouts found for scene:", currentScene);
                return;
            }
            
            // Find the current layout object
            var layoutObj = layouts.find(layout => layout.name === currentLayout);
            if (!layoutObj) {
                log("Could not find layout object for:", currentLayout);
                return;
            }
            
            log("Found layout:", layoutObj);
            
            // Extract the width and height from the layout
            var layoutWidth = layoutObj.width;
            var layoutHeight = layoutObj.height;
            
            // Calculate aspect ratio
            var aspectRatio = layoutHeight / layoutWidth;
            log("Layout dimensions:", layoutWidth, "×", layoutHeight, "ratio:", aspectRatio);
            
            // Get the container width
            var containerWidth = container.offsetWidth || container.clientWidth;
            log("Container width:", containerWidth);
            
            // Fallback: if container width is still 0, try the hypeContainer width
            if (containerWidth === 0 && hypeContainer.offsetWidth > 0) {
                containerWidth = hypeContainer.offsetWidth;
                log("Using hypeContainer width as fallback:", containerWidth);
            }
            
            // Calculate the new height based on the aspect ratio
            var newHeight = Math.round(containerWidth * aspectRatio);
            log("New calculated height:", newHeight);
            
            var resolvedHeight = newHeight;

            if (dataMinHeight && dataMinHeight.indexOf('%') === -1) {
                var numericMin = parseFloat(dataMinHeight);
                if (!isNaN(numericMin)) {
                    resolvedHeight = Math.max(resolvedHeight, numericMin);
                }
            }

            // CRITICAL: Set height on the OUTER wrapper FIRST
            // This ensures the inner div's 100% height has something to reference
            container.style.height = resolvedHeight + "px";
            container.style.width = "100%";
            applyMinHeight(dataMinHeight);
            
            // Then set on the Hype container itself
            hypeContainer.style.width = "100%";
            hypeContainer.style.height = resolvedHeight + "px";
            
            // Apply to all HYPE_document elements
            var hypeDocElements = hypeContainer.querySelectorAll('.HYPE_document');
            for (var i = 0; i < hypeDocElements.length; i++) {
                hypeDocElements[i].style.width = "100%"; 
                hypeDocElements[i].style.height = resolvedHeight + "px";
            }
            
            // Also apply to any hype_container elements
            var innerContainers = hypeContainer.querySelectorAll('[id*="hype_container"]');
            for (var i = 0; i < innerContainers.length; i++) {
                innerContainers[i].style.width = "100%";
                innerContainers[i].style.height = resolvedHeight + "px";
            }
            
            if (typeof hypeDocument.relayoutIfNecessary === 'function') {
                hypeDocument.relayoutIfNecessary();
            }
            
            log("Height update complete - outer wrapper:", resolvedHeight, "px - inner container:", resolvedHeight, "px");
        }
        
        // Initial update with delay to ensure DOM is ready
        setTimeout(updateHeight, 50);
        
        // Update on window resize with debounce
        var resizeTimeout;
        window.addEventListener("resize", function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(updateHeight, 100);
        });
        
        // Update on scene changes
        hypeDocument.addEventListener("HypeSceneLoad", function() {
            setTimeout(updateHeight, 100);
        });
        
        // Update on symbol loads
        hypeDocument.addEventListener("HypeSymbolLoad", function() {
            setTimeout(updateHeight, 100);
        });
        
        // Handle layout changes
        hypeDocument.addEventListener("HypeLayoutRequest", function(hypeDocument, element, event) {
            setTimeout(updateHeight, 100);
        });
        
        // Save original continueAfterSceneTransition to chain it
        if (typeof hypeDocument.continueAfterSceneTransition === 'function') {
            var originalContinueAfterSceneTransition = hypeDocument.continueAfterSceneTransition;
            hypeDocument.continueAfterSceneTransition = function() {
                setTimeout(updateHeight, 100);
                return originalContinueAfterSceneTransition.apply(hypeDocument, arguments);
            };
        } else {
            hypeDocument.continueAfterSceneTransition = function() {
                setTimeout(updateHeight, 100);
                return true;
            };
        }
        
        log("Auto height handler initialized for", hypeDocument.documentId());
    }
    
    /**
     * Register the HypeDocumentLoad event listener
     * This ensures handleHypeAutoHeight is called ONLY when Hype documents are loaded
     */
    if (window.HYPE_eventListeners === undefined) {
        window.HYPE_eventListeners = Array();
    }
    
    window.HYPE_eventListeners.push({
        type: "HypeDocumentLoad",
        callback: hypeAutoHeightOnDocumentLoad
    });
    
    log("Hype Auto Height event listener registered - will initialize when HypeDocumentLoad fires");
    
    // Debug function that can be called from console
    window.debugHypeAutoHeight = function(enable) {
        DEBUG = enable !== false;
        log("Debug mode " + (DEBUG ? "enabled" : "disabled"));
    };
    
    log("Hype Auto Height script initialized");
})();
