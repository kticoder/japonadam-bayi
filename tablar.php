<?php
function tailwind_issues_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'japonadam_issues';
    $category_table_name = $wpdb->prefix . 'japonadam_issue_categories';

    // Veritabanı tablosu yoksa oluştur
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            issue_title tinytext NOT NULL,
            issue_description text NOT NULL,
            solution text NOT NULL,
            category_id mediumint(9) NOT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        );";

        dbDelta($sql);
    }

    // Kategori tablosu yoksa oluştur
    if($wpdb->get_var("SHOW TABLES LIKE '$category_table_name'") != $category_table_name) {
        $sql = "CREATE TABLE $category_table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_name tinytext NOT NULL,
            PRIMARY KEY  (id)
        );";

        dbDelta($sql);
    }

    // Form gönderildiğinde
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_id'])) {
            $delete_id = $_POST['delete_id'];
            $wpdb->delete($table_name, array('id' => $delete_id));
            // Modal göster
            show_success_modal();
        } elseif (isset($_POST['category_name'])) {
            // Kategori ekleme işlemi
            $category_name = $_POST['category_name'];
            $wpdb->insert(
                $category_table_name,
                array(
                    'category_name' => $category_name
                )
            );
            show_success_modal();
        } elseif (isset($_POST['delete_category_id'])) {
            // Kategori ekleme işlemi
            $delete_category_id = $_POST['delete_category_id'];
            $wpdb->delete($category_table_name, array('id' => $delete_category_id));

            show_success_modal();
        } elseif (isset($_POST['issue_title']) && isset($_POST['issue_description']) && isset($_POST['solution']) && isset($_POST['category_id'])) {
            // Sorun ekleme işlemi
            $issue_title = $_POST['issue_title'];
            $issue_description = $_POST['issue_description'];
            $solution = $_POST['solution'];
            $category_id = $_POST['category_id'];

            $wpdb->insert(
                $table_name,
                array(
                    'issue_title' => $issue_title,
                    'issue_description' => $issue_description,
                    'solution' => $solution,
                    'category_id' => $category_id
                )
            );

            // Modal göster
            show_success_modal();
    }
}

    // Veritabanından kayıtları çek
    $results = $wpdb->get_results("SELECT i.*, c.category_name FROM $table_name i
                                  LEFT JOIN $category_table_name c ON i.category_id = c.id");

    // Kategorileri çek
    $categories = $wpdb->get_results("SELECT * FROM $category_table_name");

    // Sekme başlıkları
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'issues';
    ?>
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.16/dist/tailwind.min.css">
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <div class="wrap">
        <h1 class="text-2xl font-bold mb-4">Japonadam Sorun ve Çözümleri</h1>

        <!-- Sekme başlıkları -->
        <nav class="bg-gray-200 rounded-t-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex">
                        <a href="?page=tablar&tab=issues" class="<?php echo $active_tab == 'issues' ? 'bg-white shadow-md rounded-t-lg' : ''; ?> inline-flex items-center px-4 pt-4 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Sorun ve Çözümler
                        </a>
                        <a href="?page=tablar&tab=categories" class="<?php echo $active_tab == 'categories' ? 'bg-white shadow-md rounded-t-lg' : ''; ?> ml-4 inline-flex items-center px-4 pt-4 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Kategoriler
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Sorun ve Çözümler Sekmesi -->
        <?php if ($active_tab == 'issues') { ?>
        <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <form method="post" action="" class="mb-4">
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="issue_title">
                        Sorun Başlığı
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="issue_title" name="issue_title" type="text" placeholder="Sorun başlığını girin">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="issue_description">
                        Sorun Açıklaması
                    </label>
                    <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="issue_description" name="issue_description" placeholder="Sorunu detaylı olarak açıklayın"></textarea>
                </div>
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="solution">
                    Çözüm
                </label>
                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="solution" name="solution" placeholder="Soruna bulduğunuz çözümü açıklayın"></textarea>
                <script>
                    CKEDITOR.replace('solution');
                </script>
            </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="category_id">
                        Kategori
                    </label>
                    <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="category_id" name="category_id">
                        <option value="">Kategori Seçin</option>
                        <?php foreach ($categories as $category) { ?>
                        <option value="<?php echo $category->id; ?>"><?php echo $category->category_name; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="flex items-center justify-between">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                        Kaydet
                    </button>
                </div>
            </form>

            <h2 class="text-xl font-bold mb-4">Kayıtlı Sorun ve Çözümler</h2>
            <div class="bg-white shadow-md rounded">
                <table class="w-full table-auto">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left">Sorun Başlığı</th>
                            <th class="px-4 py-3 text-left">Detaylar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row) { ?>
                        <tr class="border-b">
                            <td class="px-4 py-3"><?php echo $row->issue_title; ?></td>
                            <td class="px-4 py-3">
                                <button type="button" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="toggleDetails(<?php echo $row->id; ?>)">+</button>
                                <form method="post" action="" style="display:inline;">
                                    <input type="hidden" name="delete_id" value="<?php echo $row->id; ?>">
                                    <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Sil</button>
                                </form>
                                <div id="details-<?php echo $row->id; ?>" style="display: none;">
                                    <p><strong>Çözüm:</strong> <?php echo $row->solution; ?></p>
                                    <p><strong>Kategori:</strong> <?php echo $row->category_name; ?></p>
                                </div>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <script>
            function toggleDetails(id) {
                var details = document.getElementById('details-' + id);
                if (details.style.display === 'none') {
                    details.style.display = 'block';
                } else {
                    details.style.display = 'none';
                }
            }
            </script>
        </div>
        <?php } ?>

        <!-- Kategoriler Sekmesi -->
        <?php if ($active_tab == 'categories') { ?>
        <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <form method="post" action="" class="mb-4">
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="category_name">
                        Kategori Adı
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="category_name" name="category_name" type="text" placeholder="Kategori adını girin">
                </div>
                <div class="flex items-center justify-between">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                        Kategori Ekle
                    </button>
                </div>
            </form>

            <h2 class="text-xl font-bold mb-4">Mevcut Kategoriler</h2>
            <div class="bg-white shadow-md rounded">
                <table class="w-full table-auto">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left">Kategori Adı</th>
                            <th class="px-4 py-3 text-left">Sil</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category) { ?>
                        <tr class="border-b">
                            <td class="px-4 py-3"><?php echo $category->category_name; ?></td>
                            <td class="px-4 py-3">
                                <form method="post" action="">
                                    <input type="hidden" name="delete_category_id" value="<?php echo $category->id; ?>">
                                    <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Sil</button>
                                </form>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php } ?>
    </div>
    <?php
}
function show_success_modal() {
            echo '
            <div class="fixed z-10 inset-0 overflow-y-auto" id="successModal">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                    </div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" role="dialog" aria-modal="true" aria-labelledby="modal-headline">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline">
                                        Başarılı
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">Kayıt başarıyla tamamlandı!</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal()">
                                Kapat
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                function closeModal() {
                    document.getElementById("successModal").style.display = "none";
                }
                document.getElementById("successModal").style.display = "block";
            </script>';
}

function list_issues(WP_REST_Request $request) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'japonadam_issues';
    $category_table_name = $wpdb->prefix . 'japonadam_issue_categories';

    $results = $wpdb->get_results("
        SELECT i.*, c.category_name 
        FROM $table_name i
        LEFT JOIN $category_table_name c ON i.category_id = c.id
    ");

    $issues = array();
    foreach ($results as $row) {
        $issues[] = array(
            'id' => $row->id,
            'issue_title' => $row->issue_title,
            'issue_description' => $row->issue_description,
            'solution' => $row->solution,
            'category_name' => $row->category_name,
            'created_at' => $row->created_at
        );
    }

    return new WP_REST_Response($issues, 200);
}

add_action('rest_api_init', function () {
    register_rest_route('sitekisitlama/v1', '/issues', array(
        'methods' => 'GET',
        'callback' => 'list_issues',
        'permission_callback' => '__return_true',
    ));
});