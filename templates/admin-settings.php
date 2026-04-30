<?php
/**
 * Template for the ChatBudgie admin settings page
 */

if (!defined('ABSPATH')) {
    exit;
}

$index_status = $this->get_index_status();
?>
<div class="wrap">
    <h1><?php echo esc_html__('ChatBudgie Settings', 'chatbudgie'); ?></h1>
    
    <!-- Index Status Section -->
    <div style="background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
        <h2 style="margin-top: 0; margin-bottom: 15px;"><?php echo esc_html__('Index Status', 'chatbudgie'); ?></h2>
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <p style="font-size: 16px; font-weight: 600; margin: 0;">
                    <?php echo esc_html__('Status:', 'chatbudgie'); ?>
                    <span style="color: <?php
                        echo $index_status['status'] === 'running' ? '#f59e0b' :
                            ($index_status['status'] === 'completed' ? '#10b981' :
                            ($index_status['status'] === 'failed' ? '#ef4444' : '#667eea'));
                    ?>; font-size: 18px;">
                        <?php
                        echo esc_html(ucfirst($index_status['status']));
                        ?>
                    </span>
                </p>
                <?php if ($index_status['scheduled_posts_count'] > 0): ?>
                <p style="font-size: 12px; color: #666; margin: 5px 0 0;">
                    <?php
                    printf(
                        esc_html__('Indexing: %d of %d posts completed', 'chatbudgie'),
                        intval($index_status['completed_posts_count']),
                        intval($index_status['scheduled_posts_count'])
                    );
                    ?>
                </p>
                <?php if ($index_status['progress'] > 0 && $index_status['progress'] < 100): ?>
                <progress value="<?php echo esc_attr($index_status['progress']); ?>" max="100" style="width: 100%; height: 20px; margin-top: 5px;"></progress>
                <p style="font-size: 12px; color: #10b981; margin: 3px 0 0;">
                    <?php
                    printf(
                        esc_html__('Progress: %d%%', 'chatbudgie'),
                        intval($index_status['progress'])
                    );
                    ?>
                </p>
                <?php endif; ?>
                <?php endif; ?>
                <?php if (isset($index_status['error']) && $index_status['error']): ?>
                <p style="font-size: 12px; color: #ef4444; margin: 5px 0 0;">
                    <?php
                    printf(
                        esc_html__('Error: %s', 'chatbudgie'),
                        esc_html($index_status['error'])
                    );
                    ?>
                </p>
                <?php endif; ?>
            </div>
            <div>
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=chatbudgie_rebuild_index'), 'chatbudgie_rebuild_index')); ?>" 
                   style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px; padding: 10px 20px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s ease;">
                    <?php echo esc_html__('Rebuild Index', 'chatbudgie'); ?>
                </a>
                <p style="font-size: 12px; color: #666; margin: 5px 0 0; text-align: center;">
                    <?php echo esc_html__('Runs in background via Action Scheduler', 'chatbudgie'); ?>
                </p>
            </div>
        </div>
    </div>
    
    <div style="background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
        <h2 style="margin-top: 0; margin-bottom: 15px;"><?php echo esc_html__('Token Management', 'chatbudgie'); ?></h2>
        <div style="display: flex; align-items: center; gap: 20px;">
            <div>
                <p style="font-size: 16px; font-weight: 600; margin: 0;">
                    <?php echo esc_html__('Remaining Tokens:', 'chatbudgie'); ?> <span style="color: #667eea; font-size: 24px;"><?php echo esc_html(get_option('chatbudgie_tokens', 1000)); ?></span>
                </p>
                <p style="font-size: 12px; color: #666; margin: 5px 0 0;">
                    <?php echo esc_html__('Number of tokens available for API calls', 'chatbudgie'); ?>
                </p>
            </div>
            <button type="button" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px; padding: 10px 20px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                <?php echo esc_html__('Recharge Tokens', 'chatbudgie'); ?>
            </button>
        </div>
    </div>
    <form method="post" action="options.php">
        <?php settings_fields('chatbudgie_settings'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php echo esc_html__('Chat Bubble Icon', 'chatbudgie'); ?></th>
                <td>
                    <?php $icon_type = get_option('chatbudgie_icon_type', 'default'); ?>
                    <label style="display: block; margin-bottom: 10px;">
                        <input type="radio" name="chatbudgie_icon_type" value="default" <?php checked($icon_type, 'default'); ?> />
                        <span style="margin-left: 8px;"><?php echo esc_html__('Default Icon', 'chatbudgie'); ?></span>
                        <span style="margin-left: 10px; display: inline-block; width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; vertical-align: middle; text-align: center; line-height: 40px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="vertical-align: middle;">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </span>
                    </label>
                    <label style="display: block; margin-bottom: 10px;">
                        <input type="radio" name="chatbudgie_icon_type" value="robot" <?php checked($icon_type, 'robot'); ?> />
                        <span style="margin-left: 8px;"><?php echo esc_html__('Robot', 'chatbudgie'); ?></span>
                        <span style="margin-left: 10px; display: inline-block; width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; vertical-align: middle; text-align: center; line-height: 40px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="vertical-align: middle;">
                                <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                                <circle cx="12" cy="5" r="2"></circle>
                                <path d="M12 7v4"></path>
                                <line x1="8" y1="16" x2="8" y2="16"></line>
                                <line x1="16" y1="16" x2="16" y2="16"></line>
                            </svg>
                        </span>
                    </label>
                    <label style="display: block; margin-bottom: 10px;">
                        <input type="radio" name="chatbudgie_icon_type" value="headphones" <?php checked($icon_type, 'headphones'); ?> />
                        <span style="margin-left: 8px;"><?php echo esc_html__('Customer Service', 'chatbudgie'); ?></span>
                        <span style="margin-left: 10px; display: inline-block; width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; vertical-align: middle; text-align: center; line-height: 40px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="vertical-align: middle;">
                                <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path>
                                <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>
                            </svg>
                        </span>
                    </label>
                    <label style="display: block; margin-bottom: 10px;">
                        <input type="radio" name="chatbudgie_icon_type" value="message" <?php checked($icon_type, 'message'); ?> />
                        <span style="margin-left: 8px;"><?php echo esc_html__('Message', 'chatbudgie'); ?></span>
                        <span style="margin-left: 10px; display: inline-block; width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; vertical-align: middle; text-align: center; line-height: 40px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="vertical-align: middle;">
                                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                            </svg>
                        </span>
                    </label>
                    <label style="display: block; margin-bottom: 10px;">
                        <input type="radio" name="chatbudgie_icon_type" value="budgie" <?php checked($icon_type, 'budgie'); ?> />
                        <span style="margin-left: 8px;"><?php echo esc_html__('小鸟 (Budgie)', 'chatbudgie'); ?></span>
                        <span style="margin-left: 10px; display: inline-block; width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; vertical-align: middle; text-align: center; line-height: 40px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;">
                                <path d="M16 7h.01"/>
                                <path d="M3.4 18H12a8 8 0 0 0 8-8V7a4 4 0 0 0-7.28-2.3L2 20"/>
                                <path d="m20 7 2 .5-2 .5"/>
                                <path d="M10 18v3"/>
                                <path d="M14 17.75V21"/>
                                <path d="M7 18a6 6 0 0 0 3.84-10.61"/>
                            </svg>
                        </span>
                    </label>
                    <label style="display: block; margin-bottom: 10px;">
                        <input type="radio" name="chatbudgie_icon_type" value="custom" <?php checked($icon_type, 'custom'); ?> />
                        <span style="margin-left: 8px;"><?php echo esc_html__('自定义图标 URL', 'chatbudgie'); ?></span>
                    </label>
                    <div id="custom-icon-url" style="margin-left: 28px; margin-top: 10px; <?php echo $icon_type === 'custom' ? '' : 'display: none;'; ?>">
                        <input type="url" name="chatbudgie_custom_icon" value="<?php echo esc_attr(get_option('chatbudgie_custom_icon')); ?>" class="regular-text" placeholder="https://example.com/icon.svg" />
                        <p class="description"><?php echo esc_html__('输入自定义图标的 URL 地址（支持 SVG、PNG、JPG 格式）', 'chatbudgie'); ?></p>
                    </div>
                </td>
            </tr>
        <tr valign="top">
            <th scope="row"><?php echo esc_html__('OpenRouter API 配置', 'chatbudgie'); ?></th>
            <td>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html__('API Key', 'chatbudgie'); ?></th>
                        <td>
                            <input type="password" name="chatbudgie_openrouter_api_key" value="<?php echo esc_attr(get_option('chatbudgie_openrouter_api_key')); ?>" class="regular-text" />
                            <p class="description"><?php echo esc_html__('Your API key for authentication', 'chatbudgie'); ?></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var radios = document.querySelectorAll('input[name="chatbudgie_icon_type"]');
            var customUrlDiv = document.getElementById('custom-icon-url');
            radios.forEach(function(radio) {
                radio.addEventListener('change', function() {
                    customUrlDiv.style.display = this.value === 'custom' ? 'block' : 'none';
                });
            });

            // Token recharge functionality
            var rechargeButton = document.querySelector('button[type="button"]');
            if (rechargeButton) {
                rechargeButton.addEventListener('click', function() {
                    var amount = prompt('<?php echo esc_js(__('Please enter the number of tokens to recharge:', 'chatbudgie')); ?>', '1000');
                    if (amount && !isNaN(amount) && amount > 0) {
                        var currentTokens = parseInt('<?php echo esc_js(get_option('chatbudgie_tokens', 1000)); ?>');
                        var newTokens = currentTokens + parseInt(amount);

                        // Create hidden field to store new token amount
                        var tokenField = document.getElementById('chatbudgie_tokens');
                        if (!tokenField) {
                            tokenField = document.createElement('input');
                            tokenField.type = 'hidden';
                            tokenField.id = 'chatbudgie_tokens';
                            tokenField.name = 'chatbudgie_tokens';
                            document.querySelector('form').appendChild(tokenField);
                        }
                        tokenField.value = newTokens;

                        // Show success message
                        alert('<?php echo esc_js(__('Recharge successful!', 'chatbudgie')); ?> \n<?php echo esc_js(__('New token amount:', 'chatbudgie')); ?> ' + newTokens);

                        // Update display
                        var tokenDisplay = document.querySelector('span[style*="color: #667eea"]');
                        if (tokenDisplay) {
                            tokenDisplay.textContent = newTokens;
                        }
                    }
                });
            }
        });
        </script>
        <?php submit_button(); ?>
    </form>
</div>
