<?php
/*
Plugin Name: Japon Adam Bayi
Description: Woocommerce ile Aktivasyon Anahtarı Yönetimi - Bayi
Version: 1.3
Author: [melih&ktidev]
*/

require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/kticoder/japonadam-bayi',
	__FILE__,
	'japonadam-bayi'
);

$myUpdateChecker->setBranch('main');
$myUpdateChecker->getVcsApi()->enableReleaseAssets();

function generate_activation_key_for_order($order_id) {
    $order = wc_get_order($order_id);
    $user_id = $order->get_user_id();
    $user_email = $order->get_billing_email();
    $purchased_products = array();

    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $quantity = $item->get_quantity();
        $purchased_products[] = array(
            'product_id' => $product_id,
            'quantity' => $quantity
        );
    }

    $response = wp_remote_post('https://japonadam.com/wp-json/mylisans/v1/generate-activation-key', array(
        'method' => 'POST',
        'timeout' => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array(),
        'body' => array(
            'user_id' => $user_id,
            'user_email' => $user_email,
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
    $user_id = $current_user->ID;

    // REST API URL'si
    $api_url = 'https://japonadam.com/wp-json/mylisans/v1/get-activation-code';
    $site_url = get_site_url();
    # sadece http ise https yap
    if (strpos($site_url, 'http://') !== false) {
        $site_url = str_replace('http://', 'https://', $site_url);
    }
    // API isteği için parametreler
    $api_params = array(
        'user_id' => $user_id,
        'site_linki' => $site_url
    );

    // HTTP GET isteği yap
    $response = wp_remote_get(add_query_arg($api_params, $api_url));

    // Yanıtı kontrol et ve hata olup olmadığını belirle
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        echo "Bir şeyler yanlış gitti: $error_message";
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($data['success']) {
            $activation_code = $data['activation_code'];
        }  
        else {
            $activation_code =  $data['message'];
        }
    }

    // Tailwind CSS CDN
    echo '<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">';

    echo '<div class="p-6">';
    echo '<h2 class="text-3xl mb-4">Aktivasyon İşlemleri</h2>';
    echo '<p class="text-md mb-4">Dünyadaki ilk ve tek "Ultra Hassas" premium lisans sistemine hoşgeldiniz!</br></br>Sitenizin yönetici bilgilerini bizimle paylaşmanıza gerek kalmadı. Artık kendi premium lisanslarınızı hızla, güvenle ve kolaylıkla kurabilirsiniz.</br></br>Sadece 2 dakikada ürünlerinizi etkinleştirin.</p>';

    echo '<div class="grid grid-cols-1 gap-6">';
    
    // Step 1
    echo '<div class="border p-4 rounded">';
    echo '<h3 class="text-lg mb-2">1- Satın aldığınız ürünleri kurabilmek için aktivasyon anahtarınızı alın.</h3>';
    echo '<div class="bg-gray-100 p-4 rounded">';

    if ($activation_code) {
    echo '<label for="activationCode">Aktivasyon anahtarınız</label>';
    echo '<input type="text" id="activationCode" value="' . esc_attr($activation_code) . '" class="mt-1 p-2 w-full border rounded" readonly>';
} else {
    echo 'Aktivasyon anahtarınız bulunmamaktadır.';
}


    echo '</div>';
    echo '</div>';
// Çizgili kısım
    echo '<div class="h-12 border-l-4 border-dashed border-gray-300 mx-auto" style="width: 2px;"></div>';
    // Step 2
    echo '<div class="border p-4 rounded">';
    echo '<h3 class="text-lg mb-2">2- Sitenize "Japon Adam" eklentisini yükleyin ve etkinleştirin.</h3>';
    echo '<a href="https://japonadam.com/japonadam.zip" target="_blank"><button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Aktivasyon eklentisini indir</button></a>';
    echo '</div>';
// Çizgili kısım
    echo '<div class="h-12 border-l-4 border-dashed border-gray-300 mx-auto" style="width: 2px;"></div>';
    // Step 3
    echo '<div class="border p-4 rounded">';
    echo '<h3 class="text-lg mb-2">3- Yönetici panelinizdeki sol menüden Japon Adam menüsüne tıklayın.</h3>';
    echo '</div>';
// Çizgili kısım
    echo '<div class="h-12 border-l-4 border-dashed border-gray-300 mx-auto" style="width: 2px;"></div>';
    // Step 4
    echo '<div class="border p-4 rounded">';
    echo '<h3 class="text-lg mb-2">4- Japon Adam eklentinize aktivasyon anahtarınızı yapıştırın ve doğrulayın.</h3>';
    echo '<div class="bg-gray-200 h-48 rounded flex items-center justify-center">';
    echo '<img  src="https://japonadam.com/wp-content/uploads/2023/10/aktivasyon.png" class="mb-2"></img>';
    echo '</div>';
    echo '</div>';
    // Çizgili kısım
    echo '<div class="h-12 border-l-4 border-dashed border-gray-300 mx-auto" style="width: 2px;"></div>';
    // Step 5
    echo '<div class="border p-4 rounded">';
    echo '<h3 class="text-lg mb-2">5- "Satın Aldıklarım" sekmesine tıklayın ve ürünlerinizin kurulumunu yapın.</h3>';
    echo '<div class="bg-gray-200 p-4 rounded flex items-center justify-center">';
    echo '<span class="w-1/2"><img src="https://japonadam.com/wp-content/uploads/2023/10/kurulum-urun.png"></img></span>';
    echo '</div>';
    echo '</div>';
    // Çizgili kısım
    echo '<div class="h-12 border-l-4 border-dashed border-gray-300 mx-auto" style="width: 2px;"></div>';
    // Step 6
    echo '<div class="border p-4 rounded">';
    echo '<h3 class="text-lg mb-2">6- Neredeyse bitti! Lisanslama için "Lisanslama Talimatları" sayfasına gidin.</h3>';
    echo '<a href="https://japonadam.com/lisanslama-talimatlari/" target="_blank"><button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Lisanslama Talimatları sayfasına git</button></a>';
    echo '</div>';
//kapanış etiketleri
    echo '</div>'; // Closing the grid div
    echo '</div>'; // Closing the main div
}

add_action('woocommerce_account_aktivasyon_endpoint', 'custom_my_account_endpoint_content');
// Hesabım menüsüne aktivasyon anahtarını ekleme
function custom_add_my_account_menu_items($items) {
    $new_items = array();
    $new_items['aktivasyon'] = __('Aktivasyon İşlemleri', 'woocommerce');
    $new_order = array_slice($items, 0, 0, true) + $new_items + array_slice($items, 1, null, true);
    return $new_order;
}
add_filter('woocommerce_account_menu_items', 'custom_add_my_account_menu_items');

function sync_products_from_other_site() {
    // Diğer sitenin API URL'si
    $api_url = 'https://other-woocommerce-site.com/wp-json/wc/v3/products';

    // API isteği için parametreler
    $api_params = array(
        'consumer_key' => 'ck_your_consumer_key',
        'consumer_secret' => 'cs_your_consumer_secret'
    );

    // HTTP GET isteği yap
    $response = wp_remote_get(add_query_arg($api_params, $api_url));

    // Yanıtı kontrol et ve hata olup olmadığını belirle
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        error_log("Something went wrong: $error_message");
    } else {
        $products = json_decode(wp_remote_retrieve_body($response), true);

        foreach ($products as $product) {
            $existing_product_id = wc_get_product_id_by_sku($product['sku']);

            if ($existing_product_id) {
                // Ürün zaten var, güncelle
                wp_update_post(array(
                    'ID' => $existing_product_id,
                    'post_content' => $product['description'],
                    'post_excerpt' => $product['short_description']
                ));
                update_post_meta($existing_product_id, '_price', $product['price']);
                update_post_meta($new_product_id, '_sku', $product['sku']);
            } else {
                $new_product = array(
                    'post_title' => $product['name'],
                    'post_content' => $product['description'],
                    'post_excerpt' => $product['short_description'],
                    'post_status' => 'publish',
                    'post_type' => 'product',
                    'post_author' => 1
                );
                $new_product_id = wp_insert_post($new_product);
                update_post_meta($new_product_id, '_sku', $product['sku']);
                update_post_meta($new_product_id, '_price', $product['price']);

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
}
// Özel zamanlama olayını tanımla
add_filter('cron_schedules', 'custom_cron_schedules');
function custom_cron_schedules($schedules) {
    $schedules['every_ten_seconds'] = array(
        'interval' => 10, // 10 saniye
        'display'  => 'Every Ten Seconds',
    );
    return $schedules;
}

// Eğer zamanlanmış olay yoksa, yeni bir tane oluştur
if (!wp_next_scheduled('sync_products_event')) {
    wp_schedule_event(time(), 'every_ten_seconds', 'sync_products_event');
}

// Zamanlanmış olayı tetikleyen eylemi ekle
add_action('sync_products_event', 'sync_products_from_other_site');