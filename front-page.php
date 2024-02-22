<?php
/*
 * Template Name: Book Page
 */

get_header();

$args = array(
    'post_type'      => 'book',
    'posts_per_page' => -1,
);

$books_query = new WP_Query($args);

if ($books_query->have_posts()) :
?>

    <div class="container mt-5">
        <form id="book-search-form" class="row g-3">
            <div class="col-md-9">
                <label for="genre-select" class="form-label">Filter by Genre:</label>
                <?php
                $genres = get_terms(array('taxonomy' => 'genre', 'hide_empty' => false));
                ?>
                <select id="genre-select" name="genre" class="form-select">
                    <option value="">All Genres</option>
                    <?php
                    foreach ($genres as $genre) {
                        echo '<option value="' . esc_attr($genre->slug) . '">' . esc_html($genre->name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="submit" value="Search" class="btn btn-primary">
            </div>
        </form>
    </div>

    <div id="book-list" class="container mt-5">
        <div class="row">
            <?php
            while ($books_query->have_posts()) : $books_query->the_post();
                $author_name        = get_post_meta(get_the_ID(), '_author_name', true);
                $publication_year   = get_post_meta(get_the_ID(), '_publication_year', true);
                $genres             = get_the_terms(get_the_ID(), 'genre');
                $isbn               = get_post_meta(get_the_ID(), '_isbn_number', true);
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


            <?php endwhile; ?>
        </div>
    </div>




    <script>
        jQuery(document).ready(function($) {
            $('#book-search-form').submit(function() {
                var genre = $('#genre-select').val();

                $.ajax({
                    type: 'GET',
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    data: {
                        action: 'filter_books',
                        genre: genre
                    },
                    success: function(response) {
                        $('#book-list').html(response);
                    }
                });

                return false;
            });
        });
    </script>

<?php
else :
    echo 'No books found.';
endif;

get_footer();
?>