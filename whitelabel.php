<?php
// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Add menu item
add_action('admin_menu', 'whitelabel_menu');

// function whitelabel_menu() {
//     add_menu_page(
//         'Whitelabel Ayarları',
//         'Whitelabel',
//         'manage_options',
//         'whitelabel-settings',
//         'whitelabel_settings_page',
//         'dashicons-admin-generic',
//         90
//     );
// }

// Settings page
function bayilik_settings_page() {
    // Enqueue Tailwind CSS
    wp_enqueue_style('tailwind', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');

    // Check if form is submitted
    if (isset($_POST['submit'])) {
        update_option('whitelabel_logo_link', sanitize_text_field($_POST['whitelabel_logo_link']));
        update_option('whitelabel_store_url', sanitize_text_field($_POST['whitelabel_store_url']));
        update_option('whitelabel_support_url', sanitize_text_field($_POST['whitelabel_support_url']));
        echo '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                <p>Ayarlar başarıyla kaydedildi!</p>
              </div>';
    }

    $logo_link = get_option('whitelabel_logo_link');
    $store_url = get_option('whitelabel_store_url');
    $support_url = get_option('whitelabel_support_url');
    ?>
    <div class="wrap">
        <h1 class="text-3xl font-bold mb-6">Whitelabel Ayarları</h1>
        <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4 max-w-md">
            <form method="post" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="whitelabel_logo_link">
                        Logo Link
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           id="whitelabel_logo_link"
                           name="whitelabel_logo_link"
                           type="text"
                           value="<?php echo esc_attr($logo_link); ?>"
                           placeholder="Logo bağlantısını girin">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="whitelabel_store_url">
                        Mağaza URL
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           id="whitelabel_store_url"
                           name="whitelabel_store_url"
                           type="text"
                           value="<?php echo esc_attr($store_url); ?>"
                           placeholder="Mağaza URL'sini girin">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="whitelabel_support_url">
                        Destek URL
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           id="whitelabel_support_url"
                           name="whitelabel_support_url"
                           type="text"
                           value="<?php echo esc_attr($support_url); ?>"
                           placeholder="Destek URL'sini girin">
                </div>
                
                <!-- Yeni alanlar (blurlu ve devre dışı) -->
                <div class="mb-4 relative">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="whitelabel_bg_color">
                        Arka Plan Rengi
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline opacity-50"
                           id="whitelabel_bg_color"
                           name="whitelabel_bg_color"
                           type="text"
                           disabled
                           placeholder="Arka plan rengini girin">
                    <div class="absolute inset-0 flex items-center justify-center bg-gray-200 bg-opacity-75 rounded">
                        <span class="text-gray-700 font-bold">Çok Yakında</span>
                    </div>
                </div>
                
                <div class="mb-4 relative">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="whitelabel_button_colors">
                        Buton Renkleri
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline opacity-50"
                           id="whitelabel_button_colors"
                           name="whitelabel_button_colors"
                           type="text"
                           disabled
                           placeholder="Buton renklerini girin">
                    <div class="absolute inset-0 flex items-center justify-center bg-gray-200 bg-opacity-75 rounded">
                        <span class="text-gray-700 font-bold">Çok Yakında</span>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                            type="submit"
                            name="submit">
                        Ayarları Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php
}

// API Endpoint
add_action('rest_api_init', function () {
    register_rest_route('whitelabel/v1', '/settings', array(
        'methods' => 'GET',
        'callback' => 'get_whitelabel_settings',
        'permission_callback' => '__return_true'
    ));
});

function get_whitelabel_settings() {
    $logo_link = get_option('whitelabel_logo_link');
    $store_url = get_option('whitelabel_store_url');
    $support_url = get_option('whitelabel_support_url');
    
    if (empty($logo_link) && empty($store_url) && empty($support_url)) {
        return new WP_REST_Response(array('error' => 'Settings not set'), 404);
    }
    
    return new WP_REST_Response(array(
        'logo_link' => $logo_link,
        'store_url' => $store_url,
        'support_url' => $support_url
    ), 200);
}
?>