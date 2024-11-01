<?php

function sman_questions_page_handler()
{
    global $wpdb;

    $table = new SMAN_Question_Table();

    $table->prepare_items();

    $message = "";

    // echo "getDeleteStatus";

    // var_dump($table->getDeleteStatus());

    if ("delete" === $table->current_action()) {
        if (!$table->getDeleteStatus()) {
            $message =
                "<div class='error below-h2' id='message'><p>" .
                esc_html(
                    __(
                        "We could not remove this question because it has associated responses",

                        "smart-answer"
                    )
                ) .
                "</p></div>";
        } else {
            // if ( isset($_REQUEST["id"]) && is_array($_REQUEST["id"]) && $table->getDeleteStatus() )

            // {

            //     $ids = array_map("intval", $_REQUEST["id"]);

            //     $ids = array_filter($ids, function ($id) {

            //         return $id > 0;

            //     });

            //     var_dump($_REQUEST['id']);

            //     if (!empty($ids)) {

            //         $message =

            //             '<div class="updated below-h2" id="message"><p>' .

            //             esc_html(

            //                 sprintf(

            //                     __("Items deleted: %d", "smart-answer"),

            //                     count($ids)

            //                 )

            //             ) .

            //             "</p></div>";

            //     }

            // } else {

            $message =
                '<div class="updated below-h2" id="message"><p>' .
                esc_html(sprintf(__("Items deleted: %d", "smart-answer"), 1)) .
                "</p></div>";

            // }
        }
    }
    ?>



<div class="wrap">



    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>



    <h2><?php esc_html_e("Questions", "smart-answer"); ?>







        <a class="add-new-h2" href="<?php echo esc_attr(
      get_admin_url(get_current_blog_id(), "admin.php?page=question_form")
  ); ?>"><?php esc_html_e("Add new", "smart-answer"); ?></a>







    </h2>







    <?php
 echo wp_kses_post($message);

 $page = sanitize_text_field($_REQUEST["page"]);

 $page = isset($page)
     ? ($page != "questions"
         ? $page
         : "questions")
     : "questions";
 ?>







    <form id="contacts-table" method="POST">



        <input type="hidden" name="page" value="<?php echo esc_html($page); ?>" />



        <?php $table->display(); ?>



    </form>



</div>







<?php
}

function sman_form_questions_page_handler()
{
    global $wpdb;

    $table_name = $wpdb->prefix . "sman_questions";

    $message = "";

    $notice = "";

    $default = [
        "id" => null,

        "title" => "",
    ];

    if (
        isset($_REQUEST["nonce"]) &&
        wp_verify_nonce(
            sanitize_text_field(wp_unslash($_REQUEST["nonce"])),
            basename(__FILE__)
        )
    ) {
        // echo "test";

        // $oldItem = $item;

        $id = isset($_REQUEST["id"]) ? intval($_REQUEST["id"]) : 0;

        $title = isset($_REQUEST["title"])
            ? sanitize_text_field($_REQUEST["title"])
            : "";

        $atts = ["id" => $id, "title" => $title];

        $item = shortcode_atts($default, $atts);

        $item_valid = sman_validate_contact($item);

        if ($item_valid === true) {
            if ($item["id"] == 0) {
                $data_format = ["%d", "%s"];

                $result = $wpdb->insert($table_name, $item, $data_format);

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

        if (isset($_REQUEST["id"])) {
            $id = intval($_REQUEST["id"]);

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
            } else {
                $item = $default;

                $notice = esc_html(__("Invalid ID", "smart-answer"));
            }
        }
    }

    add_meta_box(
        "question_form_meta_box",

        esc_html(__("Question data", "smart-answer")),

        "sman_form_questions_meta_box_handler",

        "response",

        "normal",

        "default"
    );
    ?>


<div class="wrap">

    <div class="icon32 icon32-posts-post" id="icon-edit">

        <br>

    </div>

    <h2>



        <?php esc_html_e(
      "Question",
      "smart-answer"
  ); ?> <a class="add-new-h2" href="<?php echo esc_url(
    get_admin_url(get_current_blog_id())
),
     "admin.php?page=questions"; ?>">


            <?php esc_html_e("back to list", "smart-answer"); ?>

        </a>

    </h2>

    <?php if (!empty($notice)): ?>


    <div id="notice" class="error">



        <p><?php echo esc_html($notice); ?></p>



    </div>


    <?php endif; ?>



    <?php if (!empty($message)): ?>


    <div id="message" class="updated">



        <p><?php echo wp_kses_post($message); ?></p>



    </div>

    <?php endif; ?>

    <form id="form" method="POST">



        <input type="hidden" name="nonce" value="<?php echo esc_html(
      wp_create_nonce(basename(__FILE__))
  ); ?>" />

        <input type="hidden" name="id" value="<?php echo esc_html($item["id"]); ?>" />

        <div class="metabox-holder" id="poststuff">



            <div id="post-body">



                <div id="post-body-content">



                    <?php do_meta_boxes("response", "normal", $item); ?>



                    <input type="submit" value="<?php esc_attr_e(
         "Save",
         "smart-answer"
     ); ?>" id="sman-submit" class="button-primary" name="submit">


                </div>
            </div>
        </div>
    </form>
</div>

<?php
}

function sman_form_questions_meta_box_handler($item)
{
    ?>







<tbody>



    <div class="formdatabc">



        <form>



            <div class="form2bc">



                <p>



                    <label for="title"><?php esc_html_e("Title", "smart-answer"); ?>:</label>



                    <br>



                    <textarea pattern="/^\S.*[a-zA-Z\s]*$/g" id="title" name="title" cols="100" rows="10" oninput="removeLeadingSpace(event)"><?php echo esc_attr(
         $item["title"]
     ); ?></textarea>



                </p>



            </div>



        </form>



    </div>

    <script>
        function removeLeadingSpace(event) {

            const input = event.target;

            const inputValue = input.value;

            if (inputValue.charAt(0) === ' ') {

                input.value = inputValue.slice(1);

            }



        }
    </script>

</tbody>

<?php
}

?>