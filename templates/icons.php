<?php
/**
 * Icon templates for ChatBudgie
 */

if (!defined('ABSPATH')) {
    exit;
}

// Icon parameters are passed via $args or defined in the scope
// $icon_type, $custom_icon, $context, $size, $stroke_width

if ($icon_type === 'custom' && !empty($custom_icon)) :
    ?><img src="<?php echo esc_url($custom_icon); ?>" alt="Chat" style="width: <?php echo $size; ?>px; height: <?php echo $size; ?>px;" /><?php
elseif ($icon_type === 'robot') :
    ?><svg width="<?php echo $size; ?>" height="<?php echo $size; ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="<?php echo $stroke_width; ?>">
        <rect x="3" y="11" width="18" height="10" rx="2"></rect>
        <circle cx="12" cy="5" r="2"></circle>
        <path d="M12 7v4"></path>
        <line x1="8" y1="16" x2="8" y2="16"></line>
        <line x1="16" y1="16" x2="16" y2="16"></line>
    </svg><?php
elseif ($icon_type === 'headphones') :
    ?><svg width="<?php echo $size; ?>" height="<?php echo $size; ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="<?php echo $stroke_width; ?>">
        <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path>
        <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>
    </svg><?php
elseif ($icon_type === 'message') :
    ?><svg width="<?php echo $size; ?>" height="<?php echo $size; ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="<?php echo $stroke_width; ?>">
        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
    </svg><?php
elseif ($icon_type === 'budgie') :
    ?><svg width="<?php echo $size; ?>" height="<?php echo $size; ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="<?php echo $stroke_width; ?>" stroke-linecap="round" stroke-linejoin="round">
        <path d="M16 7h.01"/>
        <path d="M3.4 18H12a8 8 0 0 0 8-8V7a4 4 0 0 0-7.28-2.3L2 20"/>
        <path d="m20 7 2 .5-2 .5"/>
        <path d="M10 18v3"/>
        <path d="M14 17.75V21"/>
        <path d="M7 18a6 6 0 0 0 3.84-10.61"/>
    </svg><?php
else :
    ?><svg width="<?php echo $size; ?>" height="<?php echo $size; ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="<?php echo $stroke_width; ?>">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
    </svg><?php
endif;
