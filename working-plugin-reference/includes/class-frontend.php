<?php
/**
 * Frontend functionality for CrawlGuard WP
 */

if (!defined('ABSPATH')) {
    exit;
}

class CrawlGuard_Frontend {
    
    public function __construct() {
        add_action('wp_head', array($this, 'add_meta_tags'));
        add_action('wp_footer', array($this, 'add_tracking_beacon'));
    }
    
    public function add_meta_tags() {
        $options = get_option('crawlguard_options');
        
        if ($options['monetization_enabled']) {
            echo '<meta name="crawlguard-protected" content="true">' . "\n";
            echo '<meta name="ai-content-license" content="paid">' . "\n";
        }
    }
    
    public function add_tracking_beacon() {
        // Only add beacon if not admin and monetization is enabled
        if (is_admin() || wp_doing_ajax()) {
            return;
        }
        
        $options = get_option('crawlguard_options');
        if (!$options['monetization_enabled']) {
            return;
        }
        
        ?>
        <script>
        (function() {
            // Lightweight beacon for analytics
            var beacon = {
                url: '<?php echo esc_js(get_site_url()); ?>',
                timestamp: Date.now(),
                userAgent: navigator.userAgent
            };
            
            // Send beacon asynchronously
            if (navigator.sendBeacon) {
                navigator.sendBeacon('<?php echo esc_js(CRAWLGUARD_PLUGIN_URL); ?>beacon.php', JSON.stringify(beacon));
            }
        })();
        </script>
        <?php
    }
}
