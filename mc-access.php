<?php
/**
 * Plugin Name: MC Access
 * Plugin URI: https://github.com/umichcreative/mc-access/
 * Description: Add content access functionality
 * Version: 1.0.1
 * Author: U-M: Michigan Creative
 * Author URI: http://creative.umich.edu
 */

define( 'MCACCESS_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );

class MCAccess {
    static private $_accessOptions = array(
        ''     => 'None',
        'auth' => 'Authenticated Users',
//        'site' => 'Users of this Site',
    );

    static public function init()
    {
        // UPDATER SETUP
        if( !class_exists( 'WP_GitHub_Updater' ) ) {
            include_once MCACCESS_PATH .'includes'. DIRECTORY_SEPARATOR .'updater.php';
        }
        if( isset( $_GET['force-check'] ) && $_GET['force-check'] && !defined( 'WP_GITHUB_FORCE_UPDATE' ) ) {
            define( 'WP_GITHUB_FORCE_UPDATE', true );
        }
        if( is_admin() ) {
            new WP_GitHub_Updater(array(
                // this is the slug of your plugin
                'slug' => plugin_basename(__FILE__),
                // this is the name of the folder your plugin lives in
                'proper_folder_name' => dirname( plugin_basename( __FILE__ ) ),
                // the github API url of your github repo
                'api_url' => 'https://api.github.com/repos/umichcreative/mc-access',
                // the github raw url of your github repo
                'raw_url' => 'https://raw.githubusercontent.com/umichcreative/mc-access/master',
                // the github url of your github repo
                'github_url' => 'https://github.com/umichcreative/mc-access',
                 // the zip url of the github repo
                'zip_url' => 'https://github.com/umichcreative/mc-access/zipball/master',
                // wether WP should check the validity of the SSL cert when getting an update, see https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/2 and https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/4 for details
                'sslverify' => true,
                // which version of WordPress does your plugin require?
                'requires' => '3.0',
                // which version of WordPress is your plugin tested up to?
                'tested' => '3.9.1',
                // which file to use as the readme for the version number
                'readme' => 'README.md',
                // Access private repositories by authorizing under Appearance > Github Updates when this example plugin is installed
                'access_token' => '',
            ));
        }

        // ACCESS PUBLISH ADMIN OPTIONS
        add_action( 'post_submitbox_misc_actions', function( $post ){
            $mcAccess = get_post_meta( $post->ID, '_mcaccess', true );

            echo '<div class="misc-pub-section misc-pub-mcaccess">';
            echo '<h4 style="margin-bottom: 0;">Access Restrictions</h4>';
            wp_nonce_field( plugin_basename(__FILE__), 'mcaccess_publish_nonce' );
            echo '<input type="hidden" name="mcaccess" value="" />';

            foreach( self::$_accessOptions as $val => $label ) {
                echo '<label for="mc-access--'. $val .'">';
                echo '<input id="mc-access--'. $val .'" type="radio" name="mcaccess" value="'. $val .'" '.( $mcAccess == $val ? 'checked="checked" ' : null).'/> '. $label;
                echo '</label><br/>';
            }

            echo '</div>';
        });
        add_action( 'save_post', function( $pID ){
            if( !isset( $_POST['post_type'] ) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ) {
                return $pID;
            }
            else if( !wp_verify_nonce( $_POST['mcaccess_publish_nonce'], plugin_basename(__FILE__) ) ) {
                return $pID;
            }
            else if( !isset( $_POST['mcaccess'] ) ) {
                
                return $pID;
            }

            update_post_meta(
                $pID,
                '_mcaccess',
                $_POST['mcaccess'],
                get_post_meta( $pID, '_mcaccess', true )
            );
        });


        // PROTECT AND SERVE
        add_action( 'wp', function(){
            global $post;

            $mcAccess = get_post_meta( @$post->ID, '_mcaccess', true );

            switch( $mcAccess ) {
                case 'auth':
                    // check if users is logged in
                    if( !is_user_logged_in() ) {
                        wp_redirect(
                            wp_login_url(
                                get_permalink( $post )
                            )
                        );
                    }
                    break;

                case 'site':
                    $uid = get_current_user_id();
                    $bid = get_current_blog_id();

                    if( !is_user_logged_in() ) {
                        wp_redirect(
                            wp_login_url(
                                get_permalink( $post )
                            )
                        );
                    }
                    else if( !is_user_member_of_blog( $uid, $bid ) ) {
                        // @TODO: display 403 error page
                    }
                    break;

                default:
                    break;
            }
        });
    }
}
MCAccess::init();
