<?php
// Register Custom Post Type
function custom_post_type_book()
{
    $labels = array(
        'name' => _x('Books', 'Post Type General Name', 'text_domain'),
        'singular_name' => _x('Book', 'Post Type Singular Name', 'text_domain'),
    );
    $args = array(
        'label' => __('Book', 'text_domain'),
        'labels' => $labels,
        'public' => true,
        'hierarchical' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-book-alt',
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
    );
    register_post_type('book', $args);
}
add_action('init', 'custom_post_type_book', 0);

// Register Custom Taxonomy
function custom_taxonomy_genre()
{
    $labels = array(
        'name' => _x('Genres', 'Taxonomy General Name', 'text_domain'),
        'singular_name' => _x('Genre', 'Taxonomy Singular Name', 'text_domain'),
    );
    $args = array(
        'labels' => $labels,
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
    );
    register_taxonomy('genre', array('book'), $args);
}
add_action('init', 'custom_taxonomy_genre', 0);

// Add Meta Box
function add_book_meta_box()
{
    add_meta_box(
        'book_details_meta_box',
        'Book Details',
        'display_book_meta_box',
        'book',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_book_meta_box');

// Display Meta Box
function display_book_meta_box($post)
{
    wp_nonce_field('book_details_nonce', 'book_details_nonce');

    $author_name = get_post_meta($post->ID, '_author_name', true);
    $publication_year = get_post_meta($post->ID, '_publication_year', true);
    $isbn_number = get_post_meta($post->ID, '_isbn_number', true);
?>

    <label for="author_name">Author Name:</label>
    <input type="text" id="author_name" name="author_name" value="<?php echo esc_attr($author_name); ?>">

    <label for="publication_year">Publication Year:</label>
    <input type="number" id="publication_year" name="publication_year" value="<?php echo esc_attr($publication_year); ?>">

    <label for="isbn_number">ISBN Number:</label>
    <input type="text" id="isbn_number" name="isbn_number" value="<?php echo esc_attr($isbn_number); ?>">
<?php
}

// Save Meta Box Data
function save_book_meta_box_data($post_id)
{
    if (!isset($_POST['book_details_nonce']) || !wp_verify_nonce($_POST['book_details_nonce'], 'book_details_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    $author_name = isset($_POST['author_name']) ? sanitize_text_field($_POST['author_name']) : '';
    $publication_year = isset($_POST['publication_year']) ? absint($_POST['publication_year']) : '';
    $isbn_number = isset($_POST['isbn_number']) ? sanitize_text_field($_POST['isbn_number']) : '';

    // Validate ISBN length before updating post meta
    if (strlen($isbn_number) !== 10 && strlen($isbn_number) !== 13) {
        // ISBN has an invalid length, set an error flag
        update_post_meta($post_id, '_isbn_length_error', true);
        return;
    }

    update_post_meta($post_id, '_author_name', $author_name);
    update_post_meta($post_id, '_publication_year', $publication_year);

    // Don't validate ISBN here; just update the post meta

    update_post_meta($post_id, '_isbn_number', $isbn_number);
}
add_action('save_post', 'save_book_meta_box_data');

// Validate ISBN Number before post update
function validate_isbn_before_update($data, $postarr)
{
    $isbn_number = isset($postarr['isbn_number']) ? sanitize_text_field($postarr['isbn_number']) : '';

    if (!empty($isbn_number)) {
        // Check ISBN length
        if (strlen($isbn_number) !== 10 && strlen($isbn_number) !== 13) {
            // ISBN has an invalid length, prevent post update
            $data['post_status'] = 'draft';
            add_filter('redirect_post_location', 'set_isbn_length_error_message');
        } elseif (!validate_isbn($isbn_number)) {
            // ISBN is invalid, prevent post update
            $data['post_status'] = 'draft';
            add_filter('redirect_post_location', 'set_isbn_error_message');
        }
    }

    return $data;
}
add_filter('pre_post_update', 'validate_isbn_before_update', 10, 2);

// Set error message for invalid ISBN
function set_isbn_error_message($location)
{
    remove_filter('redirect_post_location', 'set_isbn_error_message');
    return add_query_arg('isbn_error', 'invalid', $location);
}

// Display error message in admin notices
function display_isbn_error_message()
{
    if (isset($_GET['isbn_error'])) {
        if ($_GET['isbn_error'] === 'invalid') {
            echo '<div class="error"><p>ISBN number is not valid. Please enter a valid ISBN.</p></div>';
        } elseif ($_GET['isbn_error'] === 'length_error') {
            echo '<div class="error"><p>ISBN number must be exactly 10 or 13 digits.</p></div>';
        }
    }
}
add_action('admin_notices', 'display_isbn_error_message');


// Validate ISBN Number
function validate_isbn($isbn)
{
    $isbn = str_replace([' ', '-'], '', $isbn);

    if (strlen($isbn) == 10 || strlen($isbn) == 13) {
        if (strlen($isbn) == 10) {
            $checkDigit = 0;
            for ($i = 0; $i < 9; $i++) {
                $checkDigit += (10 - $i) * (int)$isbn[$i];
            }
            $checkDigit = (11 - ($checkDigit % 11)) % 11;

            return $checkDigit == 10 ? $isbn[9] == 'X' : $isbn[9] == $checkDigit;
        }

        if (strlen($isbn) == 13) {
            $checkDigit = 0;
            for ($i = 0; $i < 12; $i++) {
                $multiplier = ($i % 2 == 0) ? 1 : 3;
                $checkDigit += $multiplier * (int)$isbn[$i];
            }
            $checkDigit = (10 - ($checkDigit % 10)) % 10;

            return $isbn[12] == $checkDigit;
        }
    }

    return false;
}


function filter_books()
{
    $genre = isset($_GET['genre']) ? sanitize_text_field($_GET['genre']) : '';

    $args = array(
        'post_type'      => 'book',
        'posts_per_page' => -1,
    );

    // If a specific genre is selected, add tax_query
    if (!empty($genre)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'genre',
                'field'    => 'slug',
                'terms'    => $genre,
            ),
        );
    }

    $books_query = new WP_Query($args); ?>

    <div class="row">

        <?php

        if ($books_query->have_posts()) :
            while ($books_query->have_posts()) : $books_query->the_post();
                $author_name      = get_post_meta(get_the_ID(), '_author_name', true);
                $publication_year = get_post_meta(get_the_ID(), '_publication_year', true);
                $genres            = get_the_terms(get_the_ID(), 'genre');
                $isbn             = get_post_meta(get_the_ID(), '_isbn_number', true);
        ?>



                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="card-img book-img mb-3">
                                <?php the_post_thumbnail('full'); ?>
                            </div>
                            <h2 class="card-title"><?php echo get_the_title(); ?></h2>
                            <p class="card-text">Author: <?php echo esc_html($author_name); ?></p>
                            <p class="card-text">Publication Year: <?php echo esc_html($publication_year); ?></p>
                            <p class="card-text">Genres:
                                <?php
                                if ($genres) {
                                    foreach ($genres as $genre) {
                                        echo esc_html($genre->name) . ' ';
                                    }
                                }
                                ?>
                            </p>
                            <p class="card-text">ISBN: <?php echo esc_html($isbn); ?></p>
                        </div>
                    </div>
                </div>

        <?php
            endwhile;
        else :
            echo 'No books found.';
        endif;

        wp_reset_postdata();

        die(); ?>
    </div>
<?php
}


add_action('wp_ajax_filter_books', 'filter_books');
add_action('wp_ajax_nopriv_filter_books', 'filter_books');
