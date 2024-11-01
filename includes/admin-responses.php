<?php

class SMAN_Response_Table extends WP_List_Table
{
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
        echo esc_html(__("No responses found", "smart-answer"));
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

    function column_question_id($item)
    {
        $actions = [
            "edit" => sprintf(
                '<a href="%s">%s</a>',

                esc_url(
                    add_query_arg(
                        ["page" => "response_form", "id" => $item["id"]],

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
                            "page" => wp_kses_post($_REQUEST["page"]),

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

            $item["question_id"],

            $this->row_actions($actions)
        );
    }

    function column_favorite($item)
    {
        return sprintf(
            $item["favorite"] == 1 ? esc_html__("Yes") : esc_html__("No")
        );
    }

    function column_banned($item)
    {
        return sprintf(
            $item["banned"] == 1 ? esc_html__("Yes") : esc_html__("No")
        );
    }

    function get_columns()
    {
        $columns = [
            "cb" => '<input type="checkbox" />',

            "question_id" => esc_html__("Question Id", "smart-answer"),

            "first_name" => esc_html__("First Name", "smart-answer"),

            "response_text" => esc_html__("Response Text", "smart-answer"),

            "favorite" => esc_html__("Favorite", "smart-answer"),

            "banned" => esc_html__("Banned", "smart-answer"),
        ];

        return $columns;
    }

    function get_sortable_columns()
    {
        $sortable_columns = [
            "question_id" => ["question_id", true],

            "user_id" => ["user_id", true],

            "response_text" => ["response_text", false],

            "first_name" => ["first_name", false],

            "favorite" => ["favorite", true],

            "banned" => ["banned", true],
        ];

        return $sortable_columns;
    }

    function get_bulk_actions()
    {
        $actions = [
            "delete" => "Delete",
        ];

        return $actions;
    }

    function process_bulk_action()
    {
        global $wpdb;

        if ("delete" === $this->current_action()) {
            if (isset($_REQUEST["id"])) {
                // Check if $_REQUEST["id"] is set and is an array

                if (is_array($_REQUEST["id"])) {
                    // Filter each element in the array

                    $_REQUEST["id"] = array_filter($_REQUEST["id"], function (
                        $value
                    ) {
                        return filter_var($value, FILTER_VALIDATE_INT) !==
                            false;
                    });

                    // Re-index the array

                    $ids = array_values($_REQUEST["id"]);
                } else {
                    $ids = [intval($_REQUEST["id"])];
                }
            } else {
                $ids = [];
            }

            if (!empty($ids)) {
                $ids = array_map("intval", $ids);

                $ids = array_filter($ids);

                $ids_placeholder = implode(
                    ",",
                    array_fill(0, count($ids), "%d")
                );

                // $prepare_values = array_merge( array( $new_status ), $ids );

                // $wordcamp_id_placeholders = implode( ', ', array_fill( 0, count( $wordcamp_ids ), '%d' ) );

                // $prepare_values = array_merge( array( $new_status ), $wordcamp_ids );

                // $wpdb->query( $wpdb->prepare( "UPDATE `$table_name`SET `post_status` = %s WHERE ID IN ( $wordcamp_id_placeholders )", $prepare_values) );

                $prepared_query = $wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}sman_responses WHERE id IN ($ids_placeholder)",
                    $ids
                );

                $wpdb->query($prepared_query);
            }
        }
    }

    function prepare_items()
    {
        global $wpdb;

        $per_page = 10;

        $columns = $this->get_columns();

        $hidden = [];

        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];

        $this->process_bulk_action();

        $total_items = intval(
            $wpdb->get_var(
                "SELECT COUNT(id) FROM {$wpdb->prefix}sman_responses"
            )
        );

        $paged = isset($_REQUEST["paged"])
            ? max(0, intval($_REQUEST["paged"]) - 1)
            : 0;

        $sortable_columns = $this->get_sortable_columns();

        $orderby =
            isset($_REQUEST["orderby"]) &&
            array_key_exists(
                sanitize_key($_REQUEST["orderby"]),
                $sortable_columns
            )
                ? sanitize_key($_REQUEST["orderby"])
                : "question_id";

        $order =
            isset($_REQUEST["order"]) &&
            in_array(sanitize_sql_orderby($_REQUEST["order"]), ["asc", "desc"])
                ? sanitize_sql_orderby($_REQUEST["order"])
                : "asc";

        $per_page = intval($per_page);

        $paged = intval($paged);

        $per_page = min(max($per_page, 1), 100);

        $offset = $paged * $per_page;

        $this->items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sman_responses ORDER BY %1s %1s LIMIT %d OFFSET %d",

                $orderby,
                $order,
                $per_page,
                $offset
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

function sman_validate_response($item)
{
    $messages = [];

    if (empty($item["question_id"])) {
        $messages[] = esc_html__("Question Id is required", "smart-answer");
    }

    if (empty($item["user_id"])) {
        $messages[] = esc_html__("User Id is required", "smart-answer");
    }

    if (empty($item["first_name"])) {
        $messages[] = esc_html__("Name is required", "smart-answer");
    }

    if (empty($item["response_text"])) {
        $messages[] = esc_html__("Response Text is required", "smart-answer");
    }

    if (empty($messages)) {
        return true;
    }

    return implode("<br />", $messages);
}

?>