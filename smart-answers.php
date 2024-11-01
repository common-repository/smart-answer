<?php

/**
 * Plugin Name: Smart Answers
 * Description: Create custom questions, deploy via shortcodes, manage responses, mark favorites or ban.
 * Version: 1.0
 * Author: @softcialdeveloper
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

defined("ABSPATH") or die("¡Sin trampas!");

global $sman_db_version;

$sman_db_version = "1.0";

function sman_custom_admin_styles()
{
    wp_enqueue_style(
        "custom-admin-styles",

        plugins_url("/css/admin_styles.css", __FILE__)
    );

    wp_register_script(
        "admin-ajax",

        plugins_url("/js/admin-ajax.js", __FILE__)
    );

    $_array = [
        "ajaxurl" => admin_url("admin-ajax.php"),
    ];

    wp_localize_script("admin-ajax", "ajax_object", $_array);

    wp_enqueue_script("admin-ajax");
}

add_action("admin_enqueue_scripts", "sman_custom_admin_styles");

if (!class_exists("WP_List_Table")) {
    require_once ABSPATH . "wp-admin/includes/class-wp-list-table.php";
}

function sman_admin_menu()
{
    add_menu_page(
        esc_html__("Smart Answers", "smart-answer"),

        esc_html__("Smart Answers", "smart-answer"),

        "activate_plugins",

        "questions"
    );

    /*QUESTIONS*/

    add_submenu_page(
        "questions",

        esc_html__("Questions", "smart-answer"),

        esc_html__("Questions", "smart-answer"),

        "activate_plugins",

        "questions",

        "sman_questions_page_handler"
    );

    add_submenu_page(
        "questions",

        esc_html__("New Question", "smart-answer"),

        esc_html__("New Question", "smart-answer"),

        "activate_plugins",

        "question_form",

        "sman_form_questions_page_handler"
    );

    /*RESPONSES*/

    add_submenu_page(
        "questions",

        esc_html__("Responses", "smart-answer"),

        esc_html__("Responses", "smart-answer"),

        "activate_plugins",

        "responses",

        "sman_responses_page_handler"
    );

    add_submenu_page(
        "questions",

        esc_html__("New Response", "smart-answer"),

        esc_html__("New Response", "smart-answer"),

        "activate_plugins",

        "response_form",

        "sman_form_responses_page_handler"
    );
}

add_action("admin_menu", "sman_admin_menu");

require plugin_dir_path(__FILE__) . "includes/admin-questions.php";

require plugin_dir_path(__FILE__) . "includes/metabox-questions.php";

require plugin_dir_path(__FILE__) . "includes/admin-responses.php";

require plugin_dir_path(__FILE__) . "includes/metabox-responses.php";

// Register the shortcode

function sman_user_response_shortcode($atts)
{
    // Check if user is logged in

    if (!is_user_logged_in()) {
        return esc_html__("Please sign in to participate", "smart-answer");
    }

    // Extract shortcode attributes

    $a = shortcode_atts(
        [
            "display_question" => "yes",

            "questionid" => "",

            "minchars" => 0,

            "allow_update" => "no",

            "learn_dash_mark_as_complete" => "no",
        ],

        $atts
    );

    $display_question = filter_var(
        strtolower($a["display_question"]) === "yes" ? "true" : "false",

        FILTER_VALIDATE_BOOLEAN
    );

    $allow_update = filter_var(
        strtolower($a["allow_update"]) === "yes" ? "true" : "false",

        FILTER_VALIDATE_BOOLEAN
    );

    $learn_dash_mark_as_complete = filter_var(
        strtolower($a["learn_dash_mark_as_complete"]) === "yes"
            ? "true"
            : "false",

        FILTER_VALIDATE_BOOLEAN
    );

    // var_dump($a);

    $question_id = intval($a["questionid"]);

    $min_chars = intval($a["minchars"]);

    $min_chars_error_message = sprintf(
        esc_html__(
            "Attention: Your answer has not been saved. It must be at least %d characters and you typed ",

            "smart-answer"
        ),

        $min_chars
    );

    // Enqueue scripts

    // wp_enqueue_script('jquery');

    wp_register_script(
        "sman-ajax-script",
        plugins_url("/js/sman-ajax.js", __FILE__),
        ["jquery"]
    );

    wp_enqueue_script("sman-ajax-script");

    // print_r(admin_url('admin-ajax.php'));

    // Pass the allow_update value to the JavaScript code

    wp_localize_script(
        "sman-ajax-script",
        "sman_ajax_obj",

        [
            "ajax_url" => admin_url("admin-ajax.php"),

            "nonce" => wp_create_nonce("sman-ajax-nonce"),

            "user_id" => get_current_user_id(),

            "allow_update" => $allow_update,

            "learn_dash_mark_as_complete" => $learn_dash_mark_as_complete,

            "min_chars_error_message" => $min_chars_error_message,
        ]
    );

    // Check if the user has already submitted a response

    $current_user = wp_get_current_user();

    $user_id = $current_user->ID;

    $response = sman_get_user_response($question_id, $user_id);

    if (!is_null($response)) {
        $plugin_url = plugins_url("/assets/", __FILE__);

        $loading_gif = $plugin_url . "loading.gif";

        $html_question = $display_question
            ? "<p style='text-align: left;'><label><b>{$response->title}</b></label></p>"
            : "";

        $savingText = esc_html__("Saving", "smart-answer");

        $saveText = esc_html__("Save", "smart-answer");

        $placeholderText = sprintf(
            esc_html__("Minimum %d characters", "smart-answer"),

            $a["minchars"]
        );

        $responses_text = "";

        if (isset($responses->response_text)) {
            $responses_text = $responses->response_text;
        }

        $readonly = "";

        if (!$allow_update) {
            $readonly = "readonly";
        }

        $output = "

            <div id='sman-container'>

                {$html_question}

                <textarea 

                    placeholder='{$placeholderText}'

                    id='sman-response'

                    data-questionid='{$a["questionid"]}'

                    data-minchars='{$a["minchars"]}'{$readonly}>{$responses_text}</textarea>



                <button id='sman-submit'>

                    <span class='saveBtnTxt d-none'><img style='width: 15px;position: relative;top:-3px;left:-3px;' src='{$loading_gif}'></i> {$savingText}</span>

                    <span class='saveBtnTxt'>{$saveText}</span>

                </button>



                <div id='sman-success'></div>

                <div id='sman-error'></div>

            </div>";

        // var_dump($response);

        if (!$allow_update) {
            if ($response) {
                $yourAnswerText = esc_html__("Your asnswer", "smart-answer");

                $output = "<p>{$yourAnswerText}:</p><textarea class='textarea-read' readonly>{$responses_text}</textarea>";
            }
        }

        return $output;
    } else {
        $output =
            "<p>" .
            esc_html__(
                "There is no saved question with the id",

                "smart-answer"
            ) .
            ": <b>" .
            $question_id .
            "</b>";

        return $output;
    }
}

add_shortcode("user_response", "sman_user_response_shortcode");

/*RESPONSES SHORTCODE*/

function sman_display_responses_shortcode($atts)
{
    if (!is_user_logged_in()) {
        // var_dump($atts);

        //return __("Please sign in to participate","smart-answer");
    }

    // Extract shortcode attributes

    $a = shortcode_atts(
        [
            "display_question" => "yes",

            "questionid" => "",

            "numresponses" => 0,

            "favorites" => "no",
        ],

        $atts
    );

    wp_localize_script(
        "sman-ajax",
        "sman_ajax_obj",

        [
            "ajax_url" => admin_url("admin-ajax.php"),

            "nonce" => wp_create_nonce("sman-ajax-nonce"),

            "user_id" => get_current_user_id(),
        ]
    );

    $display_question = filter_var(
        strtolower($a["display_question"]) === "yes" ? "true" : "false",

        FILTER_VALIDATE_BOOLEAN
    );

    $favorites = filter_var(
        strtolower($a["favorites"]) === "yes" ? "true" : "false",

        FILTER_VALIDATE_BOOLEAN
    );

    // var_dump($favorites);

    $question_id = intval($a["questionid"]);

    $num_responses = intval($a["numresponses"]);

    $line = '<hr style="height:1px;border-width:0;background-color:#dee1e3">';

    // Get the responses

    $responses = sman_get_responses($question_id, $num_responses, $favorites);

    // var_dump($responses);

    if (!is_null($responses)) {
        if (is_array($responses)) {
            $html_question = $display_question
                ? "<p style='text-align: left;'><label><b>{$responses[0]->title}</b></label></p>"
                : "";

            // Display the responses

            // $output = '<div class="responses-list">' . $line;

            $output = $html_question;

            $output .= $line;

            foreach ($responses as $response) {
                $output .=
                    '<p>&quot;<i class="response">' .
                    esc_html($response->response_text) .
                    "</i>&quot;";

                $output .=
                    "<b> -" .
                    esc_html($response->first_name) .
                    "</b></p>" .
                    $line;

                //    $output .= "<br>";
            }

            //$output .= '</div>';
        } else {
            $html_question = $display_question
                ? "<p style='text-align: left;'><label><b>{$responses->title}</b></label></p>"
                : "";

            $line =
                '<hr style="height:1px;border-width:0;background-color:#dee1e3">';

            $output .= $html_question;

            $output .= $line;

            $output .=
                "<p>" .
                esc_html__(
                    "There are still no responses from other people to this question",

                    "smart-answer"
                ) .
                ".</p>";
        }

        return $output;
    } else {
        return "<p>" .
            esc_html__(
                "There is no saved question with the id",

                "smart-answer"
            ) .
            ": <b>" .
            $question_id .
            "</b>";
    }
}

add_shortcode("display_responses", "sman_display_responses_shortcode");

// AJAX function to save the response

function sman_save_response_ajax()
{
    check_ajax_referer("sman-ajax-nonce", "nonce");

    $response_text = sanitize_textarea_field($_POST["response_text"]);

    $min_chars = intval($_POST["min_chars"]);

    $curr_chars = strlen($response_text);

    $allowUpdate = filter_var($_POST["allowUpdate"], FILTER_VALIDATE_BOOLEAN);

    // Check if the response text meets the minimum character requirement

    if ($curr_chars < $min_chars) {
        wp_send_json_error([
            "message" => $min_chars_error_message . $curr_chars . ".",
        ]);
    } else {
        // Get user first name

        $current_user = wp_get_current_user();

        $first_name = $current_user->user_firstname;

        $question_id = intval($_POST["question_id"]);

        $user_id = intval($_POST["user_id"]);

        // Save the response

        $result = sman_save_user_response(
            $question_id,

            $user_id,

            $response_text,

            $first_name,

            $allowUpdate
        );

        if ($result) {
            wp_send_json_success([
                "message" =>
                    esc_html__(
                        "Your answer has been saved successfully",

                        "smart-answer"
                    ) . ".",
            ]);
        } else {
            wp_send_json_error([
                "message" =>
                    esc_html__(
                        "An error occurred saving your response",

                        "smart-answer"
                    ) . ".",
            ]);
        }
    }
}

add_action("wp_ajax_sman_save_response", "sman_save_response_ajax");

// Database functions

function sman_get_user_response($question_id, $user_id)
{
    global $wpdb;

    $t1 = $wpdb->prefix . "sman_responses";

    $t2 = $wpdb->prefix . "sman_questions";

    $query = $wpdb->prepare(
        "SELECT t1.*, t2.title FROM {$t1} as t1 INNER JOIN {$t2} as t2 ON t1.question_id = t2.id WHERE question_id = %d AND user_id = %d",

        $question_id,

        $user_id
    );

    // Execute the query

    $response = $wpdb->get_row($query);

    if ($response == null) {
        // If no response found, retrieve only the title from questions table

        $query = $wpdb->prepare(
            "SELECT title FROM {$t2} WHERE id = %d",

            $question_id
        );

        $response = $wpdb->get_row($query);
    }

    return $response;
}

function sman_save_user_response(
    $question_id,

    $user_id,

    $response_text,

    $first_name,

    $allowUpdate
) {
    global $wpdb;

    $table_name = $wpdb->prefix . "sman_responses";

    if ($allowUpdate) {
        $current_value = sman_get_user_response($question_id, $user_id);

        if (isset($current_value->response_text)) {
            if ($current_value->response_text !== $response_text) {
                $result = $wpdb->update(
                    $table_name,

                    [
                        "response_text" => $response_text,
                    ],

                    [
                        "question_id" => $question_id,

                        "user_id" => $user_id,
                    ]
                );
            } else {
                $result = true; // Success
            }
        } else {
            $result = $wpdb->insert(
                $table_name,

                [
                    "question_id" => $question_id,

                    "user_id" => $user_id,

                    "response_text" => $response_text,

                    "first_name" => $first_name,
                ],

                // Define the data format for each parameter

                ["%d", "%d", "%s", "%s"]
            );
        }
    } else {
        $result = $wpdb->insert(
            $table_name,

            [
                "question_id" => $question_id,

                "user_id" => $user_id,

                "response_text" => $response_text,

                "first_name" => $first_name,
            ],

            // Define the data format for each parameter

            ["%d", "%d", "%s", "%s"]
        );
    }

    return $result;
}

function sman_get_responses($question_id, $num_responses, $favorites)
{
    global $wpdb;

    $t1 = $wpdb->prefix . "sman_responses";

    $t2 = $wpdb->prefix . "sman_questions";

    $current_user = wp_get_current_user();

    $user_id = $current_user->ID;

    $use_favorites_query = "";

    if ($favorites) {
        $use_favorites_query = "favorite = 1 AND";
    }

    $user_is_logged_in = "";

    if (is_user_logged_in()) {
        $user_is_logged_in = "AND user_id != %d";
    }

    $query = $wpdb->prepare(
        "SELECT t1.response_text, t1.first_name, t2.title FROM {$t1} as t1

         INNER JOIN {$t2} as t2 ON t1.question_id = t2.id

         WHERE {$use_favorites_query} question_id = %d {$user_is_logged_in} AND banned != 1

         ORDER BY RAND()

         LIMIT %d",

        $question_id,

        $user_id,

        $num_responses
    );

    // Execute the query

    $responses = $wpdb->get_results($query);

    if (empty($responses)) {
        // If no responses found, retrieve only the title from questions table

        $query = $wpdb->prepare(
            "SELECT title FROM {$t2} WHERE id = %d",

            $question_id
        );

        $responses = $wpdb->get_row($query);
    }

    return $responses;
}

// Activation hook to create the database table

function sman_create_table()
{
    global $wpdb;

    global $sman_db_version;

    require_once ABSPATH . "wp-admin/includes/upgrade.php";

    // Define table names

    $table_name_responses = $wpdb->prefix . "sman_responses";

    $table_name_questions = $wpdb->prefix . "sman_questions";

    // Get the charset collate

    $charset_collate = $wpdb->get_charset_collate();

    // SQL query to create the sman_responses table

    $sql_responses = "CREATE TABLE $table_name_responses (

        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,

        question_id BIGINT(20) UNSIGNED NOT NULL,

        user_id BIGINT(20) UNSIGNED NOT NULL,

        response_text TEXT NOT NULL,

        first_name VARCHAR(255) NOT NULL,

        favorite TINYINT(4) UNSIGNED NOT NULL DEFAULT 0,

        banned TINYINT(4) UNSIGNED NOT NULL DEFAULT 0,

        last_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

        PRIMARY KEY  (id),

        KEY question_id (question_id),

        KEY user_id (user_id)

    ) $charset_collate;";

    // Execute the SQL query to create the sman_responses table

    dbDelta($sql_responses);

    // SQL query to create the sman_questions table

    $sql_questions = "CREATE TABLE $table_name_questions (

        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,

        title VARCHAR(500) NOT NULL,

        last_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

        PRIMARY KEY  (id)

    ) $charset_collate;";

    // Execute the SQL query to create the sman_questions table

    dbDelta($sql_questions);

    // Check if the plugin version has changed

    if (get_site_option("sman_db_version") != $sman_db_version) {
        update_option("sman_db_version", $sman_db_version);
    } else {
        add_option("sman_db_version", $sman_db_version);
    }
}

register_activation_hook(__FILE__, "sman_create_table");

// Add the JavaScript file

function sman_enqueue_scripts()
{
    // wp_enqueue_script( "sman-ajax-script", plugin_dir_url( __FILE__) . "js/sman-ajax.js", ["jquery"], "1.0.0", true );

    wp_enqueue_style("styles", plugin_dir_url(__FILE__) . "css/styles.css");
}

add_action("wp_enqueue_scripts", "sman_enqueue_scripts");

// Add custom body class

function sman_add_bodyclass($classes)
{
    if (
        is_singular() &&
        has_shortcode(get_post()->post_content, "user_response")
    ) {
        $classes[] = "sman-shortcode";
    }

    return $classes;
}

add_filter("body_class", "sman_add_bodyclass");

function sman_get_user_first_name($user_id = null)
{
    $user_info = $user_id ? new WP_User($user_id) : wp_get_current_user();

    if ($user_info->first_name) {
        if ($user_info->last_name) {
            return $user_info->first_name . " " . $user_info->last_name;
        }

        return $user_info->first_name;
    }

    return $user_info->display_name;
}

function sman_get_user_first_name_callback()
{
    //verify nonce

    if (
        !empty($_POST["nonce"]) &&
        wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST["nonce"])),
            "get_user_first_name_nonce"
        )
    ) {
        echo esc_html(sman_get_user_first_name(intval($_POST["user_id"])));
    } else {
        echo "";
    }

    // var_dump($_POST);

    die();
}

add_action(
    "wp_ajax_sman_get_user_first_name_callback",

    "sman_get_user_first_name_callback"
);

function sman_update_db_check()
{
    global $sman_db_version;

    if (get_site_option("sman_db_version") != $sman_db_version) {
        sman_create_table();
    }
}

add_action("plugins_loaded", "sman_update_db_check");

// Delete-tables

function sman_delete_tables()
{
    global $wpdb;

    // Drop the sman_responses table

    $table_name = $wpdb->prefix . "sman_responses";

    $wpdb->query("DROP TABLE IF EXISTS $table_name");

    // Drop the sman_questions table

    $table_name = $wpdb->prefix . "sman_questions";

    $wpdb->query("DROP TABLE IF EXISTS $table_name");

    // Delete the sman_db_version option

    delete_option("sman_db_version");
}

register_uninstall_hook(__FILE__, "sman_delete_tables");

function sman_load_textdomain()
{
    load_plugin_textdomain(
        "sman",

        false,

        dirname(plugin_basename(__FILE__)) . "/languages/"
    );
}

add_action("init", "sman_load_textdomain");
?>