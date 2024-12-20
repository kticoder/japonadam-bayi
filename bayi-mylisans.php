<?php
/*
Plugin Name: Japon Adam Bayi
Description: Woocommerce ile Aktivasyon Anahtarı Yönetimi - Bayi
Version: 1.27
Author: [melih&ktidev]
*/




require 'plugin-update-checker/plugin-update-checker.php';
include 'tablar.php';
require_once plugin_dir_path(__FILE__) . 'whitelabel.php';

function whitelabel_menu() {
    add_menu_page('Bayilik Ayarları', 'Bayilik', 'manage_options', 'bayilik-settings', 'bayilik_settings_page', 'dashicons-admin-customizer', 99);
    add_submenu_page('bayilik-settings', 'Menü Sekmeleri', 'Menü Sekmeleri', 'read', 'menu-sekme', 'menu_sekme_page');
}


use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/kticoder/japonadam-bayi',
	__FILE__,
	'japonadam-bayi'
);

$myUpdateChecker->setBranch('main');
$myUpdateChecker->getVcsApi()->enableReleaseAssets();


function generate_activation_key_for_order($order_id) {
    $site_url = get_site_url();
    $order = wc_get_order($order_id);
    # user_id kullanıcının mailinin alfabedeki sıra numarasıdır.
    $user_id = array_sum(array_map('ord', str_split($order->get_billing_email())));

    $user_email = $order->get_billing_email();
    $purchased_products = array();

    foreach ($order->get_items() as $item) {
        // product id ürün sku'su
        // $product_id = $item->get_product_id();
        $product_id = $item->get_product()->get_sku();
        $quantity = $item->get_quantity();
        $purchased_products[] = array(
            'product_id' => $product_id,
            'quantity' => $quantity
        );
    }

    $response = wp_remote_post('https://japonadam.com/wp-json/mylisans/v1/generate-activation-key', array(
        'method' => 'POST',
        'timeout' => 90,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array(),
        'body' => array(
            'user_id' => $user_id,
            'user_email' => $user_email,
            'satin_alinan_site' => $site_url,
            'purchased_products' => $purchased_products
        ),
        'cookies' => array()
    ));

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        error_log("Something went wrong: $error_message");
    } else {
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if ($data['success']) {
            error_log('Activation key generated for order #' . $order_id);
        } else {
            error_log('Activation key generation failed for order #' . $order_id);
        }
    }
}
add_action('woocommerce_order_status_completed', 'generate_activation_key_for_order');


// Hesabım sayfasına endpoint ekleme
function custom_add_my_account_endpoint() {
    add_rewrite_endpoint('aktivasyon', EP_PAGES);
}
add_action('init', 'custom_add_my_account_endpoint');

// Hesabım sayfasında aktivasyon anahtarını gösterme
function custom_my_account_endpoint_content() {
    $current_user = wp_get_current_user();
    $user_email = $current_user->user_email;
    $domain = parse_url(get_site_url(), PHP_URL_HOST);
    $domain = str_replace('www.', '', $domain);
    $domain_parts = explode('.', $domain);
    $domain = $domain_parts[0];

    $api_url = 'https://japonadam.com/wp-json/mylisans/v1/get-activation-code';
    $site_url = get_site_url();
    if (strpos($site_url, 'http://') !== false) {
        $site_url = str_replace('http://', 'https://', $site_url);
    }
    $api_params = array(
        'user_email' => $user_email,
        'site_linki' => $site_url
    );

    $response = wp_remote_get(add_query_arg($api_params, $api_url));

    // Check if the site language is English
    $is_english = (get_locale() === 'en_US');

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        echo $is_english ? "Something went wrong: $error_message" : "Bir şeyler yanlış gitti: $error_message";
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($data['success']) {
            $activation_code = $data['activation_code'];
        } else {
            $activation_code = $data['message'];
        }
    }

    echo '<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">';

    echo '<div class="p-6">';
    echo '<h2 class="text-3xl mb-4">' . ($is_english ? 'Activation Procedures' : 'Aktivasyon İşlemleri') . '</h2>';
    echo '<p class="text-md mb-4">' . ($is_english ? 
        "Welcome to the world's first and only \"Ultra Sensitive\" premium licensing system!</br></br>You no longer need to share your site's admin information with us. Now you can quickly, securely, and easily set up your own premium licenses.</br></br>Activate your products in just 2 minutes." : 
        "Dünyadaki ilk ve tek \"Ultra Hassas\" premium lisans sistemine hoşgeldiniz!</br></br>Sitenizin yönetici bilgilerini bizimle paylaşmanıza gerek kalmadı. Artık kendi premium lisanslarınızı hızla, güvenle ve kolaylıkla kurabilirsiniz.</br></br>Sadece 2 dakikada ürünlerinizi etkinleştirin.") . '</p>';

    echo '<div class="grid grid-cols-1 gap-6">';
    
    // Step 1
    echo '<div class="border p-4 rounded">';
    echo '<h3 class="text-lg mb-2">' . ($is_english ? '1- Get your activation key to install the products you purchased.' : '1- Satın aldığınız ürünleri kurabilmek için aktivasyon anahtarınızı alın.') . '</h3>';
    echo '<div class="bg-gray-100 p-4 rounded">';

    if ($activation_code) {
        echo '<label for="activationCode">' . ($is_english ? 'Your activation key' : 'Aktivasyon anahtarınız') . '</label>';
        echo '<input type="text" id="activationCode" value="' . esc_attr($activation_code) . '" class="mt-1 p-2 w-full border rounded" readonly>';
    } else {
        echo $is_english ? 'You do not have an activation key.' : 'Aktivasyon anahtarınız bulunmamaktadır.';
    }

    echo '</div>';
    echo '</div>';

    // Dashed line
    echo '<div class="h-12 border-l-4 border-dashed border-gray-300 mx-auto" style="width: 2px;"></div>';

    // Step 2
    echo '<div class="border p-4 rounded">';
    echo '<h3 class="text-lg mb-2">' . ($is_english ? "2- Install and activate the \"$domain\" plugin on your site." : "2- Sitenize \"$domain\" eklentisini yükleyin ve etkinleştirin.") . '</h3>';
    echo '<a href="https://eklenti.japonadam.com/Bayiler/' . esc_attr($domain) . '.zip" target="_blank"><button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">' . ($is_english ? 'Download Activation Plugin' : 'Aktivasyon eklentisini indir') . '</button></a>';
    echo '</div>';

    // Dashed line
    echo '<div class="h-12 border-l-4 border-dashed border-gray-300 mx-auto" style="width: 2px;"></div>';

    // Step 3
    echo '<div class="border p-4 rounded">';
    echo '<h3 class="text-lg mb-2">' . ($is_english ? "3- Click on the $domain menu in the left sidebar of your admin panel." : "3- Yönetici panelinizdeki sol menüden $domain menüsüne tıklayın.") . '</h3>';
    echo '</div>';
    echo '</h3>';
    echo '</div>';

    // Dashed line
    echo '<div class="h-12 border-l-4 border-dashed border-gray-300 mx-auto" style="width: 2px;"></div>';

    // Step 4
    echo '<div class="border p-4 rounded">';
    echo '<h3 class="text-lg mb-2">' . ($is_english ? "4- Paste your activation key into your $domain plugin and verify it." : "4- $domain eklentinize aktivasyon anahtarınızı yapıştırın ve doğrulayın.") . '</h3>';
    echo '<div class="bg-gray-200 h-48 rounded flex items-center justify-center">';
    echo '<img src="https://japonadam.com/wp-content/uploads/2023/10/aktivasyon.png" class="mb-2"></img>';
    echo '</div>';
    echo '</div>';

    // Dashed line
    echo '<div class="h-12 border-l-4 border-dashed border-gray-300 mx-auto" style="width: 2px;"></div>';

    // Step 5
    echo '<div class="border p-4 rounded">';
    echo '<h3 class="text-lg mb-2">' . ($is_english ? '5- Click on the "My Purchases" tab and install your products.' : '5- "Satın Aldıklarım" sekmesine tıklayın ve ürünlerinizin kurulumunu yapın.') . '</h3>';
    echo '<div class="bg-gray-200 p-4 rounded flex items-center justify-center">';
    echo '<span class="w-1/2"><img src="https://japonadam.com/wp-content/uploads/2023/10/kurulum-urun.png"></img></span>';
    echo '</div>';
    echo '</div>';

    // Dashed line
    echo '<div class="h-12 border-l-4 border-dashed border-gray-300 mx-auto" style="width: 2px;"></div>';

    // Step 6
    echo '<div class="border p-4 rounded">';
    echo '<h3 class="text-lg mb-2">' . ($is_english ? '6- Almost done! Go to the "Licensing Instructions" page for licensing.' : '6- Neredeyse bitti! Lisanslama için "Lisanslama Talimatları" sayfasına gidin.') . '</h3>';
    echo '<a href="'. esc_attr($site_url) .'/lisanslama-talimatlari/" target="_blank"><button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">' . ($is_english ? 'Go to Licensing Instructions page' : 'Lisanslama Talimatları sayfasına git') . '</button></a>';
    echo '</div>';

    echo '</div>'; // Closing the grid div
    echo '</div>'; // Closing the main div
}
add_action('woocommerce_account_aktivasyon_endpoint', 'custom_my_account_endpoint_content');
// Hesabım menüsüne aktivasyon anahtarını ekleme
function custom_add_my_account_menu_items($items) {
    $new_items = array();
    $is_english = (get_locale() === 'en_US');
    $new_items['aktivasyon'] = $is_english ? __('Activation Procedures', 'woocommerce') : __('Aktivasyon İşlemleri', 'woocommerce');
    $new_order = array_slice($items, 0, 0, true) + $new_items + array_slice($items, 1, null, true);
    return $new_order;
}
add_filter('woocommerce_account_menu_items', 'custom_add_my_account_menu_items');

add_action('rest_api_init', function () {
    register_rest_route('japonadambayi/v1', '/sync-products/', array(
        'methods' => 'POST',
        'callback' => 'sync_products_from_other_site',
        'permission_callback' => function () {
            // Basit bir API anahtarı kontrolü
            $api_key = isset($_GET['api_key']) ? $_GET['api_key'] : '';
            if ($api_key === 'japontetik') {
                return true;
            }
            return new WP_Error('rest_forbidden', esc_html__('Yetkilendirme başarısız.', 'my-text-domain'), array('status' => 401));
        }
    ));
});



function sync_products_from_other_site() {
    // Diğer sitenin API URL'si
    $api_url = 'https://japonadam.com/wp-json/wc/v3/products';

    $api_params = array(
        'consumer_key' => 'ck_428669908c4bd0a095f2038a45863b174d7e5f84',
        'consumer_secret' => 'cs_31e35d490f2dc19ebd126751efd341109c41af30',
        'per_page' => 100, // 100 ürünü al
        'status' => 'any' // Taslak ürünleri de dikkate almak için
    );
    // HTTP GET isteği yap
    $response = wp_remote_get(add_query_arg($api_params, $api_url));

    // Yanıtı kontrol et ve hata olup olmadığını belirle
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        error_log("Something went wrong: $error_message");
    } else {
        $source_products = json_decode(wp_remote_retrieve_body($response), true);
        $source_product_skus = array_column($source_products, 'sku');
        // Mevcut tüm ürünlerin isimlerini ve SKU'larını al
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'any', // Taslak ürünleri de dikkate almak için
            'fields' => 'ids'
        );
        $current_products = get_posts($args);
        $current_products_info = array_map(function($id) {
            return array(
                'name' => get_the_title($id),
                'sku' => get_post_meta($id, '_sku', true),
                'id' => $id
            );
        }, $current_products);

        $current_product_skus = array_map(function($id) {
            return get_post_meta($id, '_sku', true);
        }, $current_products);

        foreach ($current_products_info as $product_info) {
            if (!in_array($product_info['sku'], $source_product_skus)) {
                error_log('Deleting product with SKU: ' . $product_info['sku']);
                wp_delete_post($product_info['id'], true);
            }
        }

        foreach ($source_products as $product) {
            $found_key = array_search($product['sku'], array_column($current_products_info, 'sku'));
            // ürün yoksa yeni ürün ekle
            if ($found_key === false) {
                // Ürün yoksa yeni ürün ekle
                $new_product = array(
                    'post_title' => $product['name'],
                    // 'post_content' => 'Ürün açıklaması alanı',
                    'post_excerpt' => $product['short_description'],
                    'post_status' => 'publish', // Yeni ürünleri yayımlanmış olarak ekle
                    'post_type' => 'product',
                    'post_author' => 1
                );
                $new_product_id = wp_insert_post($new_product);
                update_post_meta($new_product_id, '_sku', $product['sku']);
                update_post_meta($new_product_id, '_price', $product['price']);

                if (!empty($product['categories'])) {
                    $category_ids = array_map(function($category) {
                        // Kategori adına göre kategori ID'sini al veya oluştur
                        if (!term_exists($category['name'], 'product_cat')) {
                            $new_category = wp_insert_term($category['name'], 'product_cat');
                            return $new_category['term_id'];
                        } else {
                            $existing_category = get_term_by('name', $category['name'], 'product_cat');
                            return $existing_category->term_id;
                        }
                    }, $product['categories']);
                    wp_set_object_terms($new_product_id, $category_ids, 'product_cat');
                }
                // Ürün resmini ekle
                if (!empty($product['images'][0]['src'])) {
                    $image_url = $product['images'][0]['src'];
                    $upload_dir = wp_upload_dir();
                    $image_data = file_get_contents($image_url);
                    $filename = basename($image_url);
                    if (wp_mkdir_p($upload_dir['path'])) {
                        $file = $upload_dir['path'] . '/' . $filename;
                    } else {
                        $file = $upload_dir['basedir'] . '/' . $filename;
                    }
                    file_put_contents($file, $image_data);

                    $wp_filetype = wp_check_filetype($filename, null);
                    $attachment = array(
                        'post_mime_type' => $wp_filetype['type'],
                        'post_title' => sanitize_file_name($filename),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    );
                    $attach_id = wp_insert_attachment($attachment, $file, $new_product_id);
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    set_post_thumbnail($new_product_id, $attach_id);
                }
            }

        }
    }
    return new WP_REST_Response(array('message' => 'Ürünler başarıyla senkronize edildi'), 200);
}
// Özel zamanlama olayını tanımla
// add_filter('cron_schedules', 'custom_cron_schedules');
// function custom_cron_schedules($schedules) {
//     $schedules['every_ten_seconds'] = array(
//         'interval' => 10, // 10 saniye
//         'display'  => 'Every Ten Seconds',
//     );
//     return $schedules;
// }

// // Eğer zamanlanmış olay yoksa, yeni bir tane oluştur
// if (!wp_next_scheduled('sync_products_event')) {
//     wp_schedule_event(time(), 'every_ten_seconds', 'sync_products_event');
// }

// // Zamanlanmış olayı tetikleyen eylemi ekle
// add_action('sync_products_event', 'sync_products_from_other_site');
// Sipariş detayları sayfasına özel alan ekleme
add_action('woocommerce_admin_order_data_after_billing_address', 'display_activation_code_in_order_details', 10, 1);
function display_activation_code_in_order_details($order){
    $order_id = $order->get_id();
    $order_email = $order->get_billing_email();

    // API URL
    $api_url = 'https://japonadam.com/wp-json/mylisans/v1/get-activation-code-by-email?email=' . urlencode($order_email);

    // API'den aktivasyon kodunu al
    $response = wp_remote_get($api_url);
    if (is_wp_error($response)) {
        $activation_code = 'Aktivasyon kodu alınamadı.';
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if ($data['success']) {
            $activation_code = $data['activation_code'];
        } else {
            $activation_code = 'Aktivasyon kodu bulunamadı.';
        }
    }

    // Aktivasyon Kodu alanını göster
    echo '<p><strong>Aktivasyon Kodu:</strong> <input type="text" value="' . esc_attr($activation_code) . '" style="width:100%;"></p>';
}


class JaponAdamNotifications {
    public function __construct() {
        add_action('admin_bar_menu', [$this, 'custom_toolbar_menu'], 999);
    }

    public function custom_toolbar_menu($wp_admin_bar) {
        $notifications = $this->get_notifications();
        $notification_count = count($notifications);

        $args = [
            'id' => 'japonadam_notifications',
            'title' => '<span class="ab-icon dashicons dashicons-bell"></span><span class="ab-label">' . $notification_count . '</span>',
            'href' => '#',
        ];
        $wp_admin_bar->add_node($args);

        foreach ($notifications as $notification) {
            $wp_admin_bar->add_node([
                'id' => 'japonadam_notification_' . $notification->id,
                'parent' => 'japonadam_notifications',
                'title' => $notification->bildirim_metni,
                'href' => '#',
            ]);
        }
    }

    private function get_notifications() {
        $response = wp_remote_get('https://japonadam.com/wp-json/bildirimler/v1/liste?sorgu=bayi');
        
        if (is_wp_error($response)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $notifications = json_decode($body);

        return is_array($notifications) ? $notifications : [];
    }
}

$japonadam_notifications = new JaponAdamNotifications();