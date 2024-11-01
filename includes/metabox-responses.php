<?php

function sman_responses_page_handler()
{
    global $wpdb;

    $table = new SMAN_Response_Table();

    $table->prepare_items();

    $message = "";

    if ("delete" === $table->current_action()) {
        if (isset($_REQUEST["id"]) && is_array($_REQUEST["id"])) {
            $sanitized_ids = array_map("intval", $_REQUEST["id"]);

            $count = count($sanitized_ids);

            $message =
                '<div class="updated below-h2" id="message"><p>' .
                esc_html(
                    sprintf(__("Items deleted: %d", "smart-answer"), $count)
                ) .
                "</p></div>";
        } else {
            $message =
                '<div class="updated below-h2" id="message"><p>' .
                esc_html(sprintf(__("Items deleted: %d", "smart-answer"), 1)) .
                "</p></div>";
        }
    }
    ?>



<div class="wrap">



    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>



    <h2><?php esc_html_e("Responses", "smart-answer"); ?>



        <a class="add-new-h2" href="<?php echo esc_attr(
      get_admin_url(get_current_blog_id(), "admin.php?page=response_form")
  ); ?>">



            <?php esc_html_e("Add new", "smart-answer"); ?></a>



    </h2>



    <?php
 echo wp_kses_post($message);

 $page = sanitize_text_field($_REQUEST["page"]);

 $page = isset($page)
     ? ($page != "responses"
         ? $page
         : "responses")
     : "responses";
 ?>



    <form id="contacts-table" method="POST">



        <input type="hidden" name="page" value="<?php echo esc_attr($page); ?>" />



        <?php $table->display(); ?>



    </form>



</div>



<?php
}

function sman_form_responses_page_handler()
{
    global $wpdb;

    $table_name = $wpdb->prefix . "sman_responses";

    $message = "";

    $notice = "";

    $default = [
        "id" => 0,

        "question_id" => 0,

        "user_id" => 0,

        "response_text" => "",

        "first_name" => "",

        "favorite" => 0,

        "banned" => 0,
    ];

    if (
        isset($_REQUEST["nonce"]) &&
        wp_verify_nonce(
            sanitize_text_field(wp_unslash($_REQUEST["nonce"])),
            basename(__FILE__)
        )
    ) {
        // $oldItem = $item;

        $id = intval($_REQUEST["id"]);

        $question_id = intval($_REQUEST["question_id"]);

        $user_id = intval($_REQUEST["user_id"]);

        $first_name = sanitize_text_field($_REQUEST["first_name"]);

        $response_text = sanitize_text_field($_REQUEST["response_text"]);

        $favorite = isset($_REQUEST["favorite"])
            ? intval($_REQUEST["favorite"])
            : 0;

        $banned = isset($_REQUEST["banned"]) ? intval($_REQUEST["banned"]) : 0;

        $data = [
            "id" => $id,

            "question_id" => $question_id,

            "user_id" => $user_id,

            "first_name" => $first_name,

            "response_text" => $response_text,

            "favorite" => $favorite,

            "banned" => $banned,
        ];

        $item = shortcode_atts($default, $data);

        $item_valid = sman_validate_response($item);

        if ($item_valid === true) {
            if ($item["id"] == 0) {
                $result = $wpdb->insert($table_name, $item);

                $item["id"] = $wpdb->insert_id;

                if ($result) {
                    $message = esc_html(
                        __("Item was successfully saved", "smart-answer")
                    );
                } else {
                    $notice = esc_html(
                        __(
                            "There was an error while saving item",

                            "smart-answer"
                        )
                    );
                }
            } else {
                $now = new DateTime();

                $now = $now->format("Y-m-d h:i:s");

                $item += ["last_updated" => $now];

                $result = $wpdb->update($table_name, $item, [
                    "id" => $item["id"],
                ]);

                if ($result) {
                    $message = esc_html(
                        __("Item was successfully updated", "smart-answer")
                    );
                } else {
                    $notice = esc_html(
                        __(
                            "There was an error while updating item",

                            "smart-answer"
                        )
                    );
                }
            }
        } else {
            $notice = $item_valid;
        }
    } else {
        $item = $default;

        $id = isset($_REQUEST["id"]) ? intval($_REQUEST["id"]) : 0;

        if ($id > 0) {
            $prepared_query = $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $id
            );

            $item = $wpdb->get_row($prepared_query, ARRAY_A);

            if (!$item) {
                $item = $default;

                $notice = esc_html(__("Item not found", "smart-answer"));
            }
        }
    }

    add_meta_box(
        "contacts_form_meta_box",

        esc_html(__("Response data", "smart-answer")),

        "sman_form_responses_meta_box_handler",

        "response",

        "normal",

        "default"
    );
    ?>



<div class="wrap">



    <div class="icon32 icon32-posts-post" id="icon-edit">



        <br>



    </div>



    <h2><?php esc_html_e(
     "Response",

     "smart-answer"
 ); ?> <a class="add-new-h2" href="<?php echo esc_attr(
     get_admin_url(get_current_blog_id(), "admin.php?page=responses")
 ); ?>">



            <?php esc_html_e("back to list", "smart-answer"); ?></a>



    </h2>



    <?php if (!empty($notice)): ?>



    <div id="notice" class="error">



        <p><?php echo wp_kses_post($notice); ?></p>



    </div>



    <?php endif; ?>



    <?php if (!empty($message)): ?>



    <div id="message" class="updated">



        <p><?php echo esc_html($message); ?></p>



    </div>



    <?php endif; ?>



    <form id="form" method="POST">



        <input type="hidden" name="nonce" value="<?php echo esc_attr(
      wp_create_nonce(basename(__FILE__))
  ); ?>" />



        <input type="hidden" name="id" value="<?php echo esc_attr($item["id"]); ?>" />



        <div class="metabox-holder" id="poststuff">



            <div id="post-body">



                <div id="post-body-content">



                    <?php do_meta_boxes("response", "normal", $item); ?>



                    <input type="submit" value="<?php esc_html_e(
         "Save",
         "smart-answer"
     ); ?>" id="submit" class="button-primary" name="submit">



                </div>



            </div>



        </div>



    </form>



</div>



<?php
}

function sman_form_responses_meta_box_handler($item)
{
    global $wpdb;

    $table_questions = $wpdb->prefix . "sman_questions";

    $table_users = $wpdb->prefix . "users";

    $questions_items = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, title FROM $table_questions WHERE 1 = %d",
            1
        ),
        ARRAY_A
    );

    $users = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID, user_email FROM $table_users WHERE 1 = %d",
            1
        ),
        ARRAY_A
    );

    if (count($questions_items) == 0) {
        $notice = esc_html(__("Add at least one question", "smart-answer"));

        $message =
            '<div class="error below-h2" id="message"><p>' .
            $notice .
            "</p></div>";

        echo wp_kses_post($message);
    }
    ?>



<tbody>
    <div class="formdatabc">
        <form>
            <div class="form2bc">
                <p>
                    <label for="question_id"><?php esc_html_e( "Question", "smart-answer" ); ?>:</label>
                    <br>

                    <!-- <input oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');" id="question_id" name="question_id" type="text" value="<?php esc_attr( $item["question_id"] ); ?>" <?php esc_attr(isset($_GET["id"])) ? "readonly" : ""; ?> > -->

                    <?php $readOnly = esc_attr(isset($_GET["id"])) ? "readonly" : ""; ?>

                    <select id="question_id_select" <?php echo esc_attr( $readOnly ); ?> onchange="val('question_id_select')">

                        <?php 
                            foreach ($questions_items as $question) 
                            {
                                $question_id = esc_attr(intval($question["id"]));

                                $question_title = esc_html(sanitize_text_field($question["title"]));

                                $selected = $question_id == intval($item["question_id"]) ? "selected" : "";

                        ?>
                                <option value="<?php echo esc_attr($question_id); ?>" <?php echo esc_attr($selected); ?>> <?php echo esc_html($question_title); ?></option>
                        <?php

                            } 
                        ?>

                    </select>

               <?php 
                    if(isset($questions_items[0]["id"]))
                    {
                        $question_id_value = $item["question_id"] == 0 ? $questions_items[0]["id"] : $item["question_id"]; 
                    }
               ?>

                    <input type="hidden" name="question_id" id="question_id" value="<?php echo esc_attr( $question_id_value ); ?>">

                </p>

                <p>
                    <label for="user_id"><?php esc_html_e("User Id", "smart-answer"); ?>:</label>
                    <br>

                    <select id="user_id_select" <?php echo esc_attr( $readOnly ); ?> onchange="val('user_id_select')">



                    <?php 
                    foreach ($users as $user) {
                        $user_id = esc_attr(intval($user["ID"]));

                        $user_email = esc_html(sanitize_email($user["user_email"]));

                        $selected = $user_id == intval($item["user_id"]) ? "selected" : "";

                    ?>
                        <option value="<?php echo esc_attr($user_id); ?>" <?php echo esc_attr($selected); ?>> <?php echo esc_html("(".$user_id.") "); echo esc_html($user_email); ?></option>
                    <?php

                    } 

                    ?>

                    </select>

                    <?php $user_id_value = $item["user_id"] == 0 ? $users[0]["ID"] : $item["user_id"]; ?>

                    <input type="hidden" name="user_id" id="user_id" value="<?php echo esc_attr( $user_id_value ); ?>">
                </p>
                <p>
                    <label for="first_name"><?php esc_html_e("First Name", "smart-answer"); ?>:</label>
                    <br>
                    <input id="first_name" name="first_name" type="text" 
                    value="<?php echo esc_attr( $item["first_name"] == "" ? sman_get_user_first_name($users[0]["ID"]) : $item["first_name"] ); ?>" oninput="removeLeadingSpace(event)">
                </p>
                <p>
                    <label for="favorite"><?php esc_html_e("Favorite", "smart-answer"); ?>:</label>
                    <br>

                    <input id="favorite" name="favorite" type="checkbox" value="1" <?php echo esc_attr( $item["favorite"] ) == "1" ? "checked" : ""; ?>>
                </p>
                <p>
                    <label for="banned"><?php esc_html_e("Banned", "smart-answer"); ?>:</label>
                    <br>
                    <input id="banned" name="banned" type="checkbox" value="1" <?php echo esc_attr( $item["banned"] ) == "1" ? "checked" : ""; ?>>
                </p>
                <p>
                    <label for="response_text"><?php esc_html_e( "Response Text", "smart-answer" ); ?>:</label>
                    <br>
                    <textarea id="response_text" name="response_text" cols="100" rows="10" 
                    oninput="removeLeadingSpace(event)"><?php echo esc_attr( $item["response_text"] ); ?></textarea>
                </p>
            </div>
        </form>
    </div>

    <script>
        function removeLeadingSpace(event) {
            const input = event.target;
            const inputValue = input.value;
            if (inputValue.charAt(0) === ' ') 
            {
                input.value = inputValue.slice(1);
            }
        }

        function val(element_id) {
            d = document.getElementById(element_id).value;
            let element = document.getElementById(element_id.replace('_select', ''));
            element.value = d;
        }

        //SET FIRST NAME INPUT VALUE
        var select = document.getElementById('user_id_select');
        select.addEventListener('change', function() {
            var selectedValue = select.value;
            console.log("selectedValue", selectedValue);

            data = {
                action: 'sman_get_user_first_name_callback',
                user_id: selectedValue,
                nonce: '<?php echo esc_html(wp_create_nonce("get_user_first_name_nonce")); ?>'
            }

            try {
                jQuery.post(window.ajaxurl, data, function(response) {
                    console.log(response);
                    var inputElement = document.getElementById("first_name");
                    inputElement.value = response;
                });
            } catch (ex) {
                console.log(ex);
            }

        });
    </script>
</tbody>

<?php
}

?>