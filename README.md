Downloaded WordPress Theme Boiler Plate from : https://underscores.me/

# Step First :

-   Download and Install WordPress.
-   Activate the given theme.
-   import database

# Step Two :

-   Login WordPress
-   Navigate to Book menu on the admin sidebar.
-   Add new
-   There you can add title , description, feature image, select genre.

Live demo link : https://testwork.handworknepal.com

Source Code : https://github.com/webramesh/WordPresstest.git

# File to check codes

-   functions.php
    Imported custom-post.php (It makes functions.php light)

```
require get_template_directory() . '/lib/custom-post.php';
```

-   lib/custom-post.php (Created lib folder and created custom-post "Book")

*   Created custom post type "Book"
*   Created taxonomy "Genre"
*   Created meta box field
*   Saved meta data
*   Verifed ISBN (10 0r 13 digit only)

# custom page added

-   It is automatically front page so no need to set custom page but if you want to add you can still add by adding page and selecting page template.
-   Select template : Book Page
