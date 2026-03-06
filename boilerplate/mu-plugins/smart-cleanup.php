<?php
/**
 * Smart Cleanup MU-Plugin
 *
 * Drop this file into wp-content/mu-plugins/ on any Voxel site.
 * It runs client-side JS to:
 *   1. Hide N/A values in post feed grids
 *   2. Collapse duplicate prices ($X – $X → $X)
 *   3. Uses MutationObserver for AJAX-loaded content
 *
 * Usage: cp mu-plugins/smart-cleanup.php path/to/wordpress/wp-content/mu-plugins/
 */

add_action("wp_footer", function () { ?>
    <script>
        (function () {
            function cleanup() {
                // 1. Hide N/A labels in feed grids
                document.querySelectorAll(".post-feed-grid span").forEach(function (el) {
                    var t = el.textContent.trim();
                    if (t === "N/A" || t.indexOf("N/A ") === 0) el.style.display = "none";
                });

                // 2. Collapse duplicate prices: "$X – $X" → "$X"
                document.querySelectorAll("p, div, span").forEach(function (el) {
                    var m = el.textContent.trim().match(/\$\s*([0-9,.]+)\s*[\u2013\u2014\-]\s*\$\s*([0-9,.]+)/);
                    if (!m || m[1].replace(/,/g, "") !== m[2].replace(/,/g, "")) return;
                    var kids = el.childNodes;
                    for (var i = kids.length - 1; i >= 0; i--) {
                        var n = kids[i];
                        if (n.nodeType === 1 && n.textContent.trim().match(/^[\u2013\u2014\-]\s*\$/))
                            n.style.display = "none";
                        if (n.nodeType === 1 && n.textContent.trim().match(/^[\u2013\u2014\-]$/)) {
                            n.style.display = "none";
                            if (n.nextSibling && n.nextSibling.nodeType === 3) n.nextSibling.textContent = "";
                        }
                    }
                });
            }

            // Run on load
            document.readyState === "loading"
                ? document.addEventListener("DOMContentLoaded", cleanup)
                : cleanup();

            // Watch for AJAX-loaded content
            if (window.MutationObserver) {
                var debounce;
                new MutationObserver(function () {
                    clearTimeout(debounce);
                    debounce = setTimeout(cleanup, 100);
                }).observe(document.body, { childList: true, subtree: true });
            }
        })();
    </script>
<?php });
