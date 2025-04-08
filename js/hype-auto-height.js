/**
 * Tumult Hype Auto Height
 * Based on HypeAutoHeight by Max Ziebell (https://github.com/worldoptimizer/HypeAutoHeight)
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
        
        // Setup resize function based on layout dimensions
        function updateHeight() {
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
            
            // Calculate the new height based on the aspect ratio
            var newHeight = Math.round(containerWidth * aspectRatio);
            log("New calculated height:", newHeight);
            
            // Apply the height to the Hype container
            hypeContainer.style.width = "100%";
            hypeContainer.style.height = newHeight + "px";
            
            // Apply to HYPE_document elements if they exist
            var hypeDocElements = hypeContainer.querySelectorAll('.HYPE_document');
            for (var i = 0; i < hypeDocElements.length; i++) {
                hypeDocElements[i].style.width = "100%"; 
                hypeDocElements[i].style.height = newHeight + "px";
            }
            
            // Also apply to any hype_container elements
            var innerContainers = hypeContainer.querySelectorAll('[id*="hype_container"]');
            for (var i = 0; i < innerContainers.length; i++) {
                innerContainers[i].style.width = "100%";
                innerContainers[i].style.height = newHeight + "px";
            }
            
            // Force Hype to update its layout
            if (typeof hypeDocument.relayoutIfNecessary === 'function') {
                hypeDocument.relayoutIfNecessary();
            }
            
            log("Height update complete");
        }
        
        // Initial update
        setTimeout(updateHeight, 10);
        
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
     * Initialize for existing Hype documents
     */
    function initExistingDocuments() {
        log("Initializing existing Hype documents");
        if ("HYPE" in window) {
            for (var documentName in HYPE.documents) {
                var hypeDoc = HYPE.documents[documentName];
                if (hypeDoc && typeof hypeDoc.documentElement === 'function') {
                    var element = hypeDoc.documentElement();
                    if (element) {
                        log("Found existing document:", documentName);
                        handleHypeAutoHeight(hypeDoc, element, {});
                    }
                }
            }
        } else {
            log("HYPE object not found in window");
        }
    }
    
    // DOM ready event listener
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(initExistingDocuments, 100);
        });
    } else {
        // DOM already loaded, initialize with a delay
        setTimeout(initExistingDocuments, 100);
    }
    
    // Add window load event to catch Hype documents that load after DOM ready
    window.addEventListener("load", function() {
        setTimeout(initExistingDocuments, 500);
    });
    
    // For future documents that will load
    if (window.HYPE_eventListeners === undefined) {
        window.HYPE_eventListeners = Array();
    }
    
    window.HYPE_eventListeners.push({
        type: "HypeDocumentLoad",
        callback: function(hypeDocument, element, event) {
            log("HypeDocumentLoad fired for new document");
            setTimeout(function() {
                handleHypeAutoHeight(hypeDocument, element, event);
            }, 100);
        }
    });
    
    // Debug function that can be called from console
    window.debugHypeAutoHeight = function(enable) {
        DEBUG = enable !== false;
        log("Debug mode " + (DEBUG ? "enabled" : "disabled"));
        
        // Re-run initialization when debug is enabled
        if (DEBUG) {
            initExistingDocuments();
        }
    };
    
    log("Hype Auto Height script initialized");
})();
