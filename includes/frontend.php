<?php

namespace EasyViewCounter;

if (!defined('ABSPATH'))
	exit;

class Frontend
{
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'post_views_count';

        $this->init_hooks();
    }

    /**
     * Initializes the WordPress hooks for admin menu,
     * script and style enqueueing, and post view count display on single post page.
     */
    private function init_hooks()
    {
        add_action('admin_menu', array($this, 'createAdminMenuItem'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAssets'));
        add_action('edit_form_after_editor', array($this, 'displayPostViewsCountOnSinglePostPage'));
    }

    /**
     * Enqueues the necessary CSS and JavaScript assets for the counter plugin.
     */
    public function enqueueAssets()
    {
        wp_enqueue_style(
            'easy-view-counter',
            plugin_dir_url(dirname(__FILE__)) . '/css/admin-style.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'easy-view-counter',
            plugin_dir_url(dirname(__FILE__)) . '/js/admin-script.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }

    /**
     * Creates an admin menu item for viewing number of post views by date.
     */
    public function createAdminMenuItem() 
    {
        $page_title = 'Easy View Counter Analytics';
        $menu_title = 'View Counter';
        $capability = 'manage_options';
        $menu_slug = 'easy-view-counter-plugin';
        $callback_function = array($this, 'getDatesAndTotalViewsCount');
        $icon_name = 'dashicons-info-outline';
        $position = 6;

        add_menu_page($page_title, $menu_title, $capability, $menu_slug, $callback_function, $icon_name, $position);
    }

    /**
     * Retrieves unique view dates and their total views,
     * and displays the results on the plugin page.
     */
    public function getDatesAndTotalViewsCount()
    {
        global $wpdb;

        // Retrieve all unique dates and sum of views for each date
        $daily_views = $wpdb->get_results("
            SELECT view_date, SUM(views) AS total_views
            FROM {$this->table_name}
            GROUP BY view_date
            ORDER BY view_date DESC
        ", ARRAY_A);

        $this->displayResultsOnPluginPage($daily_views);
    }

    /**
     * Displays the results of daily views on the plugin page.
     * 
     * @param array $daily_views An array of daily views data - dates and views count.
     */

    private function displayResultsOnPluginPage($daily_views)
    {
        ob_start();
        ?>

        <div class="wrap">
            <h1 class="main-heading">Post Views Analytics</h1>
            
            <?php if (empty($daily_views)): ?>
                <p>No records yet.</p>
            <?php else: ?>
                <table class="widefat fixed views-table-custom striped" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Total Views</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daily_views as $row): ?>
                            <tr class="accordion-row" data-date="<?= esc_attr($row['view_date']); ?>">
                                <td class="date-pointer">
                                <svg class="custom-icon-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                    <path d="M233.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L256 338.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z"/>
                                </svg>
                                <?= date("d/m/Y", strtotime($row['view_date'])); ?>
                            </td>
                                <td><?= esc_html($row['total_views']); ?></td>
                            </tr>
                            <tr class="accordion-content" style="display: none;">
                                <td colspan="2"><?= $this->displayPostsAndViewsForSpecificDate($row['view_date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php
        echo ob_get_clean();
    }

    /**
     * Displays the posts and their views count for a specific date.
     *
     * @param string $date The date for which to display views.
     * @return string HTML output for the views.
     */
    private function displayPostsAndViewsForSpecificDate($date) 
    {
        $views_results = $this->getPostsAndViewsForSpecificDate($date);

        if (empty($views_results)) {
            return '<p>No views recorded for this date.</p>';
        }

        ob_start();
        ?>

        <ul class="custom-list">
            <?php foreach ($views_results as $result): ?>
                <?php $post_permalink = get_permalink($result['ID']); ?>

                <li class="custom-list-item">
                    <span class="font-semibold">
                        <a href="<?php echo esc_url($post_permalink); ?>" class="custom-permalink">
                            <?php echo esc_html($result['post_title']); ?>
                        </a>
                    </span>
                    <svg class="custom-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                        <path d="M432 256c0 17.7-14.3 32-32 32L48 288c-17.7 0-32-14.3-32-32s14.3-32 32-32l352 0c17.7 0 32 14.3 32 32z"/>
                    </svg>
                    <span><?php echo esc_html($result['views']); ?> Views</span>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php
        return ob_get_clean();
    }

    /**
     * Retrieves views for all posts on a specific date. It gets only posts that have some views recorded.
     *
     * @param string $date The date for which to retrieve views.
     * @return array An array of posts and their view counts.
     */
    private function getPostsAndViewsForSpecificDate($date)
    {
        global $wpdb;

        // Retrieve views for all posts on a specific date
        $views_results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID, p.post_title, v.views 
                FROM {$wpdb->posts} p 
                JOIN {$this->table_name} v ON p.ID = v.post_id 
                WHERE v.view_date = %s 
                AND p.post_status = 'publish'",
                $date
            ),
            ARRAY_A
        );

        return $views_results;
    }
    
    /**
     * Displays the post views count for the last 14 days on the single post page.
     */
    public function displayPostViewsCountOnSinglePostPage()
    {
        global $post;

        // Retrieve the views data for the current post
        $data = $this->getPostViewsCountForPastFourteenDays($post->ID);
        $dates = $data['dates'];
        $views = $data['views'];
    
        ob_start();
        ?>

        <h2 class="single-post-heading-custom">Post Views for the Last 14 Days</h2>
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Views</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($i = 0; $i < count($dates); $i++): ?>
                    <tr>
                        <td><?php echo esc_html(date("d/m/Y", strtotime($dates[$i]))); ?></td>
                        <td><?php echo esc_html($views[$i]); ?></td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <?php
        echo ob_get_clean();
    }

    /**
     * Retrieves the views count for a specific post over the past 14 days.
     *
     * @param int $post_id The ID of the post to retrieve views for.
     * @param int $days The number of days to look back (default is 14).
     * @return array An associative array containing dates and view counts.
     */
    private function getPostViewsCountForPastFourteenDays($post_id, $days = 14) 
    {
        global $wpdb;
        $dates = [];
        $views = [];

        // Prepare the dates for the last $days (14 in this case)
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dates[] = $date;

            // Query to get views count for the post on specific date
            $views_result = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT views FROM {$this->table_name} WHERE post_id = %d AND view_date = %s",
                    $post_id, $date
                )
            );

            // Store the views, default to 0 if no views found
            $views[] = $views_result ? $views_result : 0;
        }

        // Return the data as an associative array
        return ['dates' => $dates, 'views' => $views];
    }
}