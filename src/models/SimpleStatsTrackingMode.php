<?php

namespace daandelange\SimpleStats;

// Since v 0.4.8, OnLoad is stable, the others are in beta (use with caution).
class SimpleStatsTrackingMode {
    const OnLoad   = 0; // Default. When the kirby router routes to a page, tracking happens. (all views are tracked, but pageload time increases a bit).
    const OnImage  = 2; // Provides a dummy image for each page, which you have to display in your html using $page->simpleStatsImage(). Tracking happens when the image is loaded (delayed).
    const Disabled = 3; // Any tracking / processing is disabled, viewing remains possible.
    const Manual   = 4; // Tracking is enabled but not automatically triggered, you have to do this yourselve. ( calling `SimpleStats::track('uid');` )
}
