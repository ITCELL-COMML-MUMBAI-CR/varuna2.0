/**
 * VARUNA System - Navbar Script (Corrected Animation & Hover Logic)
 * Current Time: Thursday, June 19, 2025 at 2:50 PM IST
 * Location: Kalyan, Maharashtra, India
 */
$(document).ready(function() {
    const navContent = $("#nav-content");
    if (navContent.length === 0) return;

    const selector = $(".hori-selector");
    const navList = navContent.find(".navbar-nav");
    let activeItem = navList.find("li.active");
    let animationTimer;

    // This function moves the indicator to a target list item
    function moveIndicatorTo(targetItem) {
        if (!targetItem || targetItem.length === 0) {
            selector.css({ "opacity": "0" });
            return;
        }
        
        selector.css({ "opacity": "1" });
        const itemPos = targetItem.position();
        const itemWidth = targetItem.innerWidth();
        
        selector.css({
            "left": itemPos.left + "px",
            "width": itemWidth + "px"
        });
    }

    // Set initial position on page load
    setTimeout(() => moveIndicatorTo(activeItem), 200);
    
    // Recalculate on window resize
    $(window).on('resize', () => setTimeout(() => moveIndicatorTo(activeItem), 500));

    // When mouse enters a top-level nav item, move the indicator there
    navList.on("mouseenter", "> li.nav-item", function() {
        // Clear any pending timer that would snap the indicator back
        clearTimeout(animationTimer);
        moveIndicatorTo($(this));
    });

    // When the mouse leaves the entire navbar area, snap back to active
    navContent.on("mouseleave", function() {
        // Start a short timer before snapping back
        animationTimer = setTimeout(() => {
            moveIndicatorTo(activeItem);
        }, 50);
    });
});