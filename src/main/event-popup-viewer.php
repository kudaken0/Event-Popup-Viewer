<?php
/**
 * Plugin Name: Event Popup Viewer
 * Description: ショートコードで直近のイベントを1件表示し、クリックで詳細とGoogleマップをポップアップ表示するプラグインです。
 * Version: 1.0.0
 * Author: kudaken
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// イベントを登録
add_action( 'init', 'epv_register_event_post_type' );
function epv_register_event_post_type() {
    $labels = ['name' => 'イベント', 'singular_name' => 'イベント', 'add_new_item' => '新規イベントを追加'];
    $args = ['labels' => $labels, 'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'menu_icon' => 'dashicons-calendar-alt', 'supports' => ['title', 'editor'], 'has_archive' => false, 'publicly_queryable' => false, 'exclude_from_search' => true];
    register_post_type( 'event', $args);
}

// カスタムフィールドの追加
add_action( 'add_meta_boxes', 'epv_add_meta_boxes' );
function epv_add_meta_boxes() {
    add_meta_box('epv_event_meta', 'イベント情報', 'epv_display_meta_box', 'event', 'normal', 'high');
}

function epv_display_meta_box( $post ) {
    wp_nonce_field( 'epv_save_meta_data', 'epv_meta_nonce' );
    // --- 既存の値を取得 ---
    $date = get_post_meta( $post->ID, '_epv_event_date', true );
    $prefecture = get_post_meta( $post->ID, '_epv_event_prefecture', true );
    $prefecture_other = get_post_meta( $post->ID, '_epv_event_prefecture_other', true ); // ★「その他」の値を取得
    $map_embed_code = get_post_meta( $post->ID, '_epv_map_embed_code', true );
    $loc_bg_color = get_post_meta( $post->ID, '_epv_location_bg_color', true ) ?: '#0073aa';
    $loc_text_color = get_post_meta( $post->ID, '_epv_location_text_color', true ) ?: '#ffffff';
    $prefectures = ["北海道", "青森県", "岩手県", "宮城県", "秋田県", "山形県", "福島県", "茨城県", "栃木県", "群馬県", "埼玉県", "千葉県", "東京都", "神奈川県", "新潟県", "富山県", "石川県", "福井県", "山梨県", "長野県", "岐阜県", "静岡県", "愛知県", "三重県", "滋賀県", "京都府", "大阪府", "兵庫県", "奈良県", "和歌山県", "鳥取県", "島根県", "岡山県", "広島県", "山口県", "徳島県", "香川県", "愛媛県", "高知県", "福岡県", "佐賀県", "長崎県", "熊本県", "大分県", "宮崎県", "鹿児島県", "沖縄県"];

    // --- フィールドのHTMLを出力 ---
    echo '<h4>開催日</h4>';
    echo '<div><input type="date" name="epv_event_date" value="' . esc_attr( $date ) . '" /></div>';

    echo '<h4 style="margin-top: 20px;">開催都道府県</h4>';
    echo '<div><select id="epv_event_prefecture" name="epv_event_prefecture">'; // ★ IDを追加
    echo '<option value="">選択してください</option>';
    foreach ($prefectures as $pref) { echo '<option value="' . esc_attr($pref) . '" ' . selected($prefecture, $pref, false) . '>' . esc_html($pref) . '</option>'; }
    echo '<option value="その他" ' . selected($prefecture, 'その他', false) . '>その他</option>'; // ★「その他」オプションを追加
    echo '</select></div>';
    
    // その他用の入力欄
    $other_style = ($prefecture === 'その他') ? 'display:block;' : 'display:none;';
    echo '<div id="epv_event_prefecture_other_wrap" style="margin-top:10px; ' . $other_style . '">';
    echo '<input type="text" id="epv_event_prefecture_other" name="epv_event_prefecture_other" value="' . esc_attr($prefecture_other) . '" placeholder="地名を入力">';
    echo '</div>';


    echo '<h4 style="margin-top: 20px;">地名タグのカラー設定</h4>';
    echo '<p style="display:flex; gap:30px;"><span>背景色:<br><input type="text" name="epv_location_bg_color" value="' . esc_attr( $loc_bg_color ) . '" class="epv-color-picker" /></span>';
    echo '<span>文字色:<br><input type="text" name="epv_location_text_color" value="' . esc_attr( $loc_text_color ) . '" class="epv-color-picker" /></span></p>';

    echo '<h4 style="margin-top: 20px;">Googleマップ埋め込みコード (任意)</h4>';
    echo '<div><textarea name="epv_map_embed_code" rows="5" style="width:100%;">' . esc_textarea( $map_embed_code ) . '</textarea></div>';
}

// 3. カスタムフィールドの値を保存
add_action( 'save_post', 'epv_save_meta_data' );
function epv_save_meta_data( $post_id ) {
    if ( ! isset( $_POST['epv_meta_nonce'] ) || ! wp_verify_nonce( $_POST['epv_meta_nonce'], 'epv_save_meta_data' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;
    
    $meta_fields = [
        'epv_event_date' => 'sanitize_text_field',
        'epv_event_prefecture' => 'sanitize_text_field',
        'epv_event_prefecture_other' => 'sanitize_text_field', // ★「その他」の保存処理を追加
        'epv_map_embed_code' => null,
        'epv_location_bg_color' => 'sanitize_hex_color',
        'epv_location_text_color' => 'sanitize_hex_color'
    ];
    foreach ($meta_fields as $key => $sanitize_callback) {
        if (isset($_POST[$key])) {
            $value = ($sanitize_callback) ? call_user_func($sanitize_callback, $_POST[$key]) : $_POST[$key];
            update_post_meta($post_id, '_' . $key, $value);
        }
    }
}

// [event_list] の処理
add_shortcode( 'event_list', 'epv_display_event_list_shortcode' );
function epv_display_event_list_shortcode() {
    $args = ['post_type' => 'event', 'posts_per_page' => 1, 'meta_key' => '_epv_event_date', 'orderby' => 'meta_value', 'order' => 'ASC', 'meta_query' => [['key' => '_epv_event_date', 'value' => date('Y-m-d'), 'compare' => '>=', 'type' => 'DATE']]];
    $events = new WP_Query( $args );
    if ( ! $events->have_posts() ) { return '<div class="epv-next-event-widget"><div class="epv-widget-header" style="background-color:'.get_option('epv_next_bg_color', '#0073aa').'; color:'.get_option('epv_next_text_color', '#ffffff').';">NEXT</div><div class="epv-widget-body"><p>予定されているイベントはありません。</p></div></div>'; }
    
    $output = '';
    while ( $events->have_posts() ) {
        $events->the_post();
        $post_id = get_the_ID();
        $date_formatted = date_i18n( 'n月j日(D)', strtotime(get_post_meta( $post_id, '_epv_event_date', true )) );
        
        // その他が選択されているか判定
        $prefecture = get_post_meta( $post_id, '_epv_event_prefecture', true );
        $location_text = ($prefecture === 'その他') ? get_post_meta( $post_id, '_epv_event_prefecture_other', true ) : $prefecture;

        $content = get_the_content();
        $map_embed_code = get_post_meta( $post_id, '_epv_map_embed_code', true );
        $map_data_attr = !empty($map_embed_code) ? ' data-map-embed="' . base64_encode($map_embed_code) . '"' : '';

        $next_bg = get_option('epv_next_bg_color', '#0073aa');
        $next_text = get_option('epv_next_text_color', '#ffffff');
        $loc_bg = get_post_meta( $post_id, '_epv_location_bg_color', true ) ?: '#0073aa';
        $loc_text = get_post_meta( $post_id, '_epv_location_text_color', true ) ?: '#ffffff';

        $next_style = 'background-color:' . esc_attr($next_bg) . '; color:' . esc_attr($next_text) . ';';
        $loc_style = 'background-color:' . esc_attr($loc_bg) . '; color:' . esc_attr($loc_text) . ';';

        $output .= '<div class="epv-next-event-widget">';
        $output .= '<div class="epv-widget-header" style="' . $next_style . '">NEXT</div>';
        $output .= '<div class="epv-widget-body">';
        $output .= '<a href="#" class="epv-event-link" data-title="' . esc_attr( get_the_title() ) . '" data-date="' . esc_attr( $date_formatted ) . '" data-content="' . esc_attr( wp_strip_all_tags($content) ) . '"' . $map_data_attr . '>';
        if (!empty($location_text)) { $output .= '<span class="epv-event-location epv-meta-tag" style="' . $loc_style . '">' . esc_html($location_text) . '</span>'; }
        if (!empty($date_formatted)) { $output .= '<span class="epv-event-date epv-meta-tag">' . esc_html($date_formatted) . '</span>'; }
        $output .= '<span class="epv-event-title">' . get_the_title() . '</span>';
        $output .= '</a></div></div>';
    }
    wp_reset_postdata();
    return $output;
}

// ポップアップのHTMLをフッターに出力
add_action( 'wp_footer', 'epv_add_popup_html_to_footer' );
function epv_add_popup_html_to_footer() { if ( is_singular() || is_page() ) { echo '<div id="epv-popup-overlay" style="display:none;"><div id="epv-popup-wrap"><div id="epv-popup-header"><h3 id="epv-popup-title"></h3><button id="epv-popup-close">&times;</button></div><div id="epv-popup-body"><p id="epv-popup-date"></p><div id="epv-popup-content"></div><div id="epv-popup-map"></div></div></div></div>'; } }

// CSSとJavaScriptの読み込み
add_action( 'wp_enqueue_scripts', 'epv_enqueue_scripts' );
function epv_enqueue_scripts() { if ( is_singular() || is_page() ) { wp_enqueue_style( 'epv-style', plugin_dir_url( __FILE__ ) . 'assets/css/style.css' ); wp_enqueue_script( 'epv-main-js', plugin_dir_url( __FILE__ ) . 'assets/js/main.js', ['jquery'], '5.2', true ); } }

// 設定メニュー
add_action('admin_menu', 'epv_add_settings_page');
function epv_add_settings_page() { add_options_page('Event Popup Viewer設定', 'Event Popup Viewer', 'manage_options', 'epv-settings', 'epv_render_settings_page'); }
function epv_render_settings_page() { ?><div class="wrap"><h1>Event Popup Viewer 設定</h1><p>ウィジェットの全体設定を行います。</p><form method="post" action="options.php"><?php settings_fields('epv_settings_group'); do_settings_sections('epv-settings'); submit_button(); ?></form></div><?php }

add_action('admin_init', 'epv_register_settings');
function epv_register_settings() {
    register_setting('epv_settings_group', 'epv_next_bg_color', 'sanitize_hex_color');
    register_setting('epv_settings_group', 'epv_next_text_color', 'sanitize_hex_color');
    add_settings_section('epv_color_section', '「NEXT」ヘッダーのカラー設定', null, 'epv-settings');
    add_settings_field('epv_next_bg_color', '背景色', 'epv_next_bg_color_callback', 'epv-settings', 'epv_color_section');
    add_settings_field('epv_next_text_color', '文字色', 'epv_next_text_color_callback', 'epv-settings', 'epv_color_section');
}
function epv_next_bg_color_callback() { echo '<input type="text" name="epv_next_bg_color" value="' . get_option('epv_next_bg_color', '#0073aa') . '" class="epv-color-picker" />'; }
function epv_next_text_color_callback() { echo '<input type="text" name="epv_next_text_color" value="' . get_option('epv_next_text_color', '#ffffff') . '" class="epv-color-picker" />'; }

// 管理画面用
add_action('admin_enqueue_scripts', 'epv_enqueue_admin_scripts');
function epv_enqueue_admin_scripts($hook) {
    if ('post.php' == $hook || 'post-new.php' == $hook || 'settings_page_epv-settings' == $hook) {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('epv-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery', 'wp-color-picker'], false, true);
    }
}