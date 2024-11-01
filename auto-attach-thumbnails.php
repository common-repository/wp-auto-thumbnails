<?php
/*
Plugin Name: Auto Attach Thumbnails
Plugin URI:  http://ilonapetrak.dev-marffa.ru/
Description: Automatically sets the post thumbnail from the first image in post if post thumbnail is not set
Version:     1.0
Author:      Ilona Petrak
Author URI:  http://ilonapetrak.dev-marffa.ru/
Text Domain: auto-attach-thumbnails
Domain Path: /languages
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
/*
Auto Attach Thumbnails is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Auto Attach Thumbnails is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Auto Attach Thumbnails. If not, see http://ilonapetrak.dev-marffa.ru/.
*/
//header("Content-type: text/html; charset=utf-8");
add_action('admin_menu', 'auat_menu');
add_action('admin_enqueue_scripts', 'auat_admin_enqueues'); // Plugin hook for adding CSS and JS files required for this plugin
function auat_menu()
{
    add_menu_page('Auto Attach Thumbnails', 'Auto Attach Thumbnails', 'read', 'auto-attach-thumbnails', 'auat_set_thumbnail', 'dashicons-format-image');
}

function auat_get_attached_image($id_post)
{
    $attached_image = get_children("order=ASC&post_parent=$id_post&post_type=attachment&post_mime_type=image&numberposts=1");
    return $attached_image;
}

function auat_get_all_posts()
{
    $arr_id = array();
    $the_query = new WP_Query( array( 'posts_per_page' => -1 ) );
    // The Loop
    if ($the_query->have_posts())
    {
        while ( $the_query->have_posts() )
        {
            $the_query->the_post();
            $id = get_the_ID();
            $already_has_thumb = has_post_thumbnail($id);
            $post_type = get_post_type($id);
            $exclude_types = array('');
            $exclude_types = apply_filters( 'eat_exclude_types', $exclude_types );

            if (!$already_has_thumb )
            {
                if ( ! in_array( $post_type, $exclude_types ) )
                {
                    // get first attached image
                    $attached_image = auat_get_attached_image($id);
                    if ($attached_image)
                    {
                        $arr_id[] = $id;
                    }
                }
            }
        }
    }
    /* Restore original Post Data */
    wp_reset_postdata();
    return $arr_id;
}

function auat_set_thumbnail()
{
    ?>
        <div class="wrap-plugin">
            <h1>Auto Attach Thumbnails</h1>
            <h4><b>NOTE! </b>Thumbnails won't be generated for posts if there's not any image attached to the post</h4>

    <?php
    $arr_post = auat_get_all_posts();
    $checked_arr = array();

    if(!empty($_POST['button-set-thumbnail']))
    {
        $checked_post = $_POST['chose_image'];
        if($_POST['set_images'] == 'all' && count($arr_post)>0)
        {
            foreach($arr_post as $post)
            {
                $checked_arr[] = $post;
            }
        }
        elseif($_POST['set_images'] == 'chose_posts' && count($checked_post)>0)
        {
            foreach($checked_post as $post)
            {
                $checked_arr[] = $post;
            }
        }
        if(count($checked_arr)>0)
        {
            foreach($checked_arr as $val)
            {
                $attached_image = auat_get_attached_image($val);
                $attachment_values = array_values($attached_image);
                // add attachment ID
                add_post_meta( $val, '_thumbnail_id', $attachment_values[0]->ID, false );
?>
    <p><?php echo 'Post '.get_the_title($val);?><span class="green"> was done</span></p>
<?php
            }
        }


    }

    $arr_post = auat_get_all_posts();
    $counter = count($arr_post);//counter for posts without thumbnails

    ?>
            <h3><?php echo $counter.' posts without post thumbnail were found'; ?></h3>
            <form method="post" action="">
                <input type="radio" name="set_images" value="all" id="all" onclick="close_posts()" checked><label for="thumbnail_for_all">All posts</label><br>
                <input type="radio" name="set_images" value="chose_posts" id="chose_posts" onclick="show_posts()"><label for="chose_posts">Chose posts</label><br>
                <div id="post_list" style='display:none'>
    <?php
        foreach($arr_post as $val)
        {
    ?>
                    <input type="checkbox" id="<?php echo $val; ?>" name="chose_image[]" value="<?php echo $val; ?>"><label for="chose_image">Post <?php echo get_the_title($val); ?></label><br>
    <?php
        }
    ?>
                            </div>

                <input type="submit" class="button-set-thumbnail" id="button-set-thumbnail" name="button-set-thumbnail" value="Set Thumbnails">
            </form>
        </div>
    <?php
}
function auat_admin_enqueues()
{
    wp_enqueue_style( 'style', plugins_url( 'css/auat-style.css', __FILE__ ) );
    wp_enqueue_script('jquery');
	wp_enqueue_script( 'auat-scripts', plugins_url( 'js/auat_script.js',__FILE__ ), array(), '20151104' );
}

?>