<?php

class SMAN_Question_Table extends WP_List_Table
{
    var $delete_status;

    function __construct()
    {
        global $status, $page;

        parent::__construct([
            "singular" => "Question",

            "plural" => "Questions",
        ]);
    }

    public function no_items()
    {
        echo esc_html(__("No questions found", "smart-answer"));
    }

    function column_default($item, $column_name)
    {
        return $item[$column_name];
    }

    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',

            esc_attr($item["id"])
        );
    }

    function column_id($item)
    {
        $page = isset($_REQUEST["page"])
            ? sanitize_text_field($_REQUEST["page"])
            : "";

        $actions = [
            "edit" => sprintf(
                '<a href="%s">%s</a>',

                esc_url(
                    add_query_arg(
                        ["page" => "question_form", "id" => $item["id"]],

                        admin_url("admin.php")
                    )
                ),

                esc_html__("Edit", "smart-answer")
            ),

            "delete" => sprintf(
                '<a href="%s">%s</a>',

                esc_url(
                    add_query_arg(
                        [
                            "page" => $page,

                            "action" => "delete",

                            "id" => $item["id"],
                        ],

                        admin_url("admin.php")
                    )
                ),

                esc_html__("Delete", "smart-answer")
            ),
        ];

        return sprintf(
            "%s %s",

            $item["id"],

            $this->row_actions($actions)
        );
    }

    function column_shortcode($item)
    {
        return '[user_response display_question="yes" questionid="' .
            $item["id"] .
            '" minchars="10" allow_update="yes" learn_dash_mark_as_complete="no"]  |  [display_responses display_question="yes" questionid="' .
            $item["id"] .
            '" numresponses="5"]';
    }

    function get_columns()
    {
        $columns = [
            "id" => esc_html__("Id", "smart-answer"),

            "title" => esc_html__("Title", "smart-answer"),

            "shortcode" => "Shortcodes",
        ];

        return $columns;
    }

    function get_sortable_columns()
    {
        $sortable_columns = [
            "id" => ["id", true],

            "title" => ["title", true],
        ];

        return $sortable_columns;
    }

    // function get_bulk_actions()

    // {

    //     $actions = array(

    //         'delete' => 'Delete'

    //     );

    //     return $actions;

    // }

    function process_bulk_action()
    {
        global $wpdb;

        // $questions_table_name = $wpdb->prefix . "sman_questions";

        // $responses_table_name = $wpdb->prefix . "sman_responses";

        if ("delete" === $this->current_action()) {
            $id = isset($_REQUEST["id"]) ? intval($_REQUEST["id"]) : [];

            // var_dump($ids);

            // if (is_array($ids)) {

            //     $ids = array_map('intval', $ids);

            // } else {

            //     $ids = [intval($ids)];

            // }

            // if (is_array($ids)) $ids = implode(',', intval($ids));

            // $placeholders = implode(',', array_fill(0, count($ids), '%d'));

            // return $wpdb->query("DELETE FROM $questions_table_name WHERE id = $ids AND NOT EXISTS (SELECT 1 FROM $responses_table_name WHERE $responses_table_name.question_id = $ids)"

            return $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}sman_questions WHERE id = %d AND NOT EXISTS ( SELECT 1 FROM {$wpdb->prefix}sman_responses as t2 WHERE t2.question_id = %d )",

                    $id,
                    $id
                )
            );
        }
    }

    function setDeleteStatus($status)
    {
        $this->delete_status = $status;
    }

    function getDeleteStatus()
    {
        return $this->delete_status;
    }

    function prepare_items()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . "sman_questions";

        $per_page = 10;

        $columns = $this->get_columns();

        $hidden = [];

        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];

        $this->setDeleteStatus($this->process_bulk_action());

        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        $paged = isset($_REQUEST["paged"])
            ? max(0, intval($_REQUEST["paged"]) - 1)
            : 0;

        $orderby =
            isset($_REQUEST["orderby"]) &&
            in_array(
                sanitize_text_field($_REQUEST["orderby"]),
                array_keys($this->get_sortable_columns())
            )
                ? sanitize_text_field($_REQUEST["orderby"])
                : "id";

        $order =
            isset($_REQUEST["order"]) &&
            in_array(sanitize_sql_orderby($_REQUEST["order"]), ["asc", "desc"])
                ? sanitize_sql_orderby($_REQUEST["order"])
                : "asc";

        $this->items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sman_questions ORDER BY %1s %1s LIMIT %d OFFSET %d",

                $orderby,
                $order,
                $per_page,
                $paged
            ),

            ARRAY_A
        );

        $this->set_pagination_args([
            "total_items" => $total_items,

            "per_page" => $per_page,

            "total_pages" => ceil($total_items / $per_page),
        ]);
    }
}

function sman_validate_contact($item)
{
    $messages = [];

    if (empty($item["title"])) {
        $messages[] = esc_html__("Title is required", "smart-answer");
    }

    if (empty($messages)) {
        return true;
    }

    return implode("<br />", $messages);
}

?>