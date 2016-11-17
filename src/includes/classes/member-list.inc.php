<?php
// @codingStandardsIgnoreFile
/**
 * Members List Query.
 *
 * Copyright: © 2009-2011
 * {@link http://websharks-inc.com/ WebSharks, Inc.}
 * (coded in the USA)
 *
 * Released under the terms of the GNU General Public License.
 * You should have received a copy of the GNU General Public License,
 * along with this software. In the main directory, see: /licensing/
 * If not, see: {@link http://www.gnu.org/licenses/}.
 *
 * @since 140502
 */
if (!defined('WPINC')) { // MUST have.
    exit('Do not access this file directly.');
}
class c_ws_plugin__s2member_pro_member_list
{
    /**
     * User query filter helper.
     *
     * @return array Search columns.
     */
    public static function _search_columns_filter()
    {
        return self::$_search_columns_for_filter;
    }
    protected static $_search_columns_for_filter = array();

    /**
     * User query (abstraction layer).
     *
     * @param array $args Query args.
     *
     * @return array ['query', 'pagination'] elements.
     */
    public static function query($args = array())
    {
        global $wpdb;

        if (!is_array($args)) {
            $args = array();
        }
        $p_var = c_ws_plugin__s2member_pro_sc_member_list_in::p_var();

        if (empty($_REQUEST[$p_var])) {
            $page = 1; // Default page number.
        } elseif (($page = (int) $_REQUEST[$p_var]) < 1) {
            $page = 1; // Default page number.
        }
        $original_args = $args; // Needed below.
        $default_args  = array( // Default query args.
            'blog_id' => $GLOBALS['blog_id'],

            'role'         => '',
            'meta_key'     => '',
            'meta_value'   => '',
            'meta_compare' => '',
            'meta_query'   => array(),

            'search'         => '',
            'search_columns' => array(
                // `wp_users`
                'ID',
                'user_login',
                'user_email',
                'user_url',
                'user_nicename',
                'display_name',

                // `wp_usermeta`
                'first_name',
                'last_name',
                'nickname',
            ),
            'include' => array(),
            'exclude' => array(),

            'order'   => 'DESC',
            'orderby' => 'registered',
            'number'  => 25,
        );
        if (!empty($args['args'])) {
            $args = wp_parse_args($args['args']);
            $args = array_merge($default_args, $args);
        } else { // Merge with individual args.
            unset($args['args']); // Do not use.
            $args = array_merge($default_args, $args);
        }
        foreach ($args as $_key => &$_value) {
            if (in_array($_key, array('count_total'), true)) {
                $_value = filter_var($_value, FILTER_VALIDATE_BOOLEAN);
            } elseif (in_array($_key, array('blog_id', 'offset', 'number'), true)) {
                $_value = (int) $_value;
            } elseif (in_array($_key, array('meta_query', 'search_columns', 'include', 'exclude'), true)) {
                $_value = $_value ? (array) $_value : array();
            } elseif (in_array($_key, array('fields'), true)) {
                $_value = is_array($_value) ? $_value : (string) $_value;
            } elseif (in_array($_key, array('role', 'search', 'who', 'meta_key', 'meta_value', 'meta_compare', 'order', 'orderby'), true)) {
                $_value = (string) $_value;
            }
        }
        unset($_key, $_value); // Housekeeping; must unset due to reference.

        /* ---------------------------------------------------------- */

        $first_last_name_meta_queries = array(
            'relaton'    => 'AND',
            'first_name' => array(
                'key'     => 'first_name',
                'value'   => '___',
                'compare' => '!=',
            ),
            'last_name' => array(
                'key'     => 'last_name',
                'value'   => '___',
                'compare' => '!=',
            ),
            'nickname' => array(
                'key'     => 'nickname',
                'value'   => '___',
                'compare' => '!=',
            ),
        );
        if ($args['meta_query']) {
            $args['meta_query'] = array(
                'relation' => 'AND',
                $args['meta_query'], $first_last_name_meta_queries,
            );
        } else {
            $args['meta_query'] = $first_last_name_meta_queries;
        }
        /* ---------------------------------------------------------- */

        if (strpos(trim($args['search'], "* \t\n\r\0\x0B"), '*') !== false) {
            $args['search'] = '"'.str_replace('*', '', $args['search']).'"';
            // Do not allow `*` to appear in the middle of a string.
            // This is currently unsupported by WP_User_Query.
            // It also creates a problem w/ usermeta regex below.
        }
        if (strlen($args['search']) >= 2 && strpos($args['search'], '*') === false && strpos($args['search'], '"') === false) {
            $args['search'] = '*'.$args['search'].'*';
        }
        $args['search'] = trim($args['search'], '"'." \t\n\r\0\x0B");
        $search_regex   = '^'.str_replace('\\*', '.*', preg_quote($args['search'])).'$';
        // Note that an ungreedy `.*?` is not possible. See: <http://jas.xyz/1PIWPZA>

        /* ---------------------------------------------------------- */

        if (!$args['search_columns']) { // Use defaults?
            $args['search_columns'] = $default_args['search_columns'];
        }
        $user_search_cols            = preg_grep('/^(?:ID|user_login|user_email|user_url|user_nicename|display_name)$/', $args['search_columns']);
        $s2_custom_field_search_cols = preg_grep('/^s2member_custom_field_\w+$/', $args['search_columns']);
        $user_meta_search_cols       = array_diff($args['search_columns'], $user_search_cols, $s2_custom_field_search_cols);

        self::$_search_columns_for_filter = $user_search_cols; // `wp_user` cols only.
        add_filter('user_search_columns', 'c_ws_plugin__s2member_pro_member_list::_search_columns_filter');

        $blog_prefix = $wpdb->get_blog_prefix($args['blog_id']); // e.g., `wp_`, etc.

        foreach ($user_meta_search_cols as &$_search_col) {
            if (stripos($_search_col, 's2member_') === 0) {
                $_search_col = $blog_prefix.$_search_col; // e.g., `wp_s2member_subscr_id`.
            } // Stored as a user option key; i.e., as a blog-specific/prefixed metadata value.
        }
        unset($_search_col); // Housekeeping; must unset due to reference.

        /* ---------------------------------------------------------- */

        $search_s2_custom_fields = true; // Default behavior.

        if (!$args['search']) {
            $search_s2_custom_fields = false;
        } elseif (!empty($original_args['search_columns']) && !$s2_custom_field_search_cols) {
            $search_s2_custom_fields = false;
        }
        /* ---------------------------------------------------------- */

        // Convert this into a complex meta query w/ multiple dimensions.
        // See: <http://codex.wordpress.org/Class_Reference/WP_Query#Custom_Field_Parameters>
        // Here we wrap what could potentially be a complex meta query already.

        if ($args['search'] && $search_regex && $user_meta_search_cols) {
            $user_meta_queries = array('relation' => 'OR');

            foreach ($user_meta_search_cols as $_search_col) {
                $user_meta_queries[] = array(
                    'key'     => $_search_col,
                    'value'   => $search_regex,
                    'compare' => 'REGEXP',
                );
            } // unset($_search_col); // Housekeeping.

            if ($user_meta_queries && $args['meta_query']) {
                $args['meta_query'] = array(
                    'relation' => 'AND', // Both!
                    $args['meta_query'], $user_meta_queries,
                );
            } elseif ($user_meta_queries) {
                $args['meta_query'] = $user_meta_queries;
            }
        }
        /* ---------------------------------------------------------- */

        $list_max = apply_filters('ws_plugin__s2member_pro_member_list_max', 250);

        $args['who']         = '';
        $args['count_total'] = true;
        $args['fields']      = 'all_with_meta';
        $args['number']      = min($args['number'], $list_max);
        $args['number']      = max(1, $args['number']);
        $args['offset']      = ($page - 1) * $args['number'];

        /* ---------------------------------------------------------- */

        if ($args['search']) {
            $query_args            = $args;
            $query_args['fields']  = 'ID';
            $query_args['orderby'] = 'ID';
            $query_args['order']   = 'ASC';
            unset($query_args['number'], $query_args['offset']);

            $user_ids = array(); // Intialize.

            if ($user_search_cols) {
                $query_args['meta_query'] = $original_args['meta_query'];
                $query                    = new WP_User_Query($query_args);
                $user_ids                 = array_merge($user_ids, $query->get_results());
            }
            if ($user_meta_search_cols) {
                unset($query_args['search']);
                $query_args['meta_query'] = $args['meta_query'];
                $query                    = new WP_User_Query($query_args);
                $user_ids                 = array_merge($user_ids, $query->get_results());
            }
            remove_filter('user_search_columns', 'c_ws_plugin__s2member_pro_member_list::_search_columns_filter');

            if ($search_s2_custom_fields) { // Also search in the serialized array of custom fields?
                if (($s2_custom_field_user_ids = self::search_s2_custom_fields($args, $s2_custom_field_search_cols))) {
                    $user_ids = array_merge($user_ids, $s2_custom_field_user_ids);
                }
            }
            if (!($user_ids = array_unique($user_ids))) {
                return array(
                    'query'      => new WP_User_Query(),
                    'pagination' => self::paginate($page, 0, $args['number']),
                );
            }
            $query_args               = $args;
            $query_args['include']    = $user_ids;
            $query_args['fields']     = 'all_with_meta';
            $query_args['meta_query'] = $original_args['meta_query'];
            unset($query_args['search'], $query_args['search_columns']);

            $query = new WP_User_Query($query_args);

            return array(
                'query'      => $query,
                'pagination' => self::paginate(
                    $page, // Current page.
                    (int) $query->get_total(),
                    $query_args['number']
                ),
            );
        } else { // Default behavior; must faster.
            $query = new WP_User_Query($args); // Args as-is.
            remove_filter('user_search_columns', 'c_ws_plugin__s2member_pro_member_list::_search_columns_filter');

            return array(
                'query'      => $query,
                'pagination' => self::paginate(
                    $page, // Current page.
                    (int) $query->get_total(),
                    $args['number']
                ),
            );
        }
    }

    /**
     * Searches s2 custom fields.
     *
     * @param array $args        Query args.
     * @param array $search_cols Custom field cols to search for.
     *                           An empty array indicates all custom fields.
     *
     * @return array User IDs to include in subsequent queries.
     */
    protected static function search_s2_custom_fields($args, $search_cols)
    {
        global $wpdb;

        if (!$args['search']) {
            return array();
        }
        $include_user_ids         = array();
        $custom_fields_regex_frag = '';

        foreach ((array) $search_cols as $_search_col) {
            if (preg_match('/^s2member_custom_field_(?P<field_id>\w+)$/', $_search_col, $_m)) {
                $custom_fields_regex_frag .= preg_quote(trim($_m['field_id'])).'|';
            }
        } // unset($_search_col, $_m); // Housekeeping.
        $custom_fields_regex_frag = rtrim($custom_fields_regex_frag, '|');

        if (!$custom_fields_regex_frag) { // All columns?
            $custom_fields_regex_frag = '.*';
        }
        $blog_prefix       = $wpdb->get_blog_prefix($args['blog_id']); // e.g., `wp_`, etc.
        $search_regex_frag = str_replace('\\*', '[^"]*', preg_quote(str_replace(array('"', '{', '}'), '', $args['search'])));
        $regex             = '(^|\{|;)s\:[0-9]+\:"('.$custom_fields_regex_frag.')"(;s\:[0-9]+\:"'.$search_regex_frag.'"|;a\:[0-9]+\:\{i\:[0-9]+;[^}]*"'.$search_regex_frag.'")';
        $users             = $wpdb->get_results('SELECT `user_id` as `ID` FROM `'.$wpdb->usermeta."` WHERE `meta_key` = '".$blog_prefix."s2member_custom_fields' AND `meta_value` REGEXP '".esc_sql($regex)."'");

        if ($users && is_array($users)) {
            foreach ($users as $_user) {
                $include_user_ids[] = $_user->ID;
            } // unset($_user);
        }
        return $include_user_ids;
    }

    /**
     * Pagination handler.
     *
     * @param int    $current_page     Current page number.
     * @param int    $total_results    Total results.
     * @param int    $per_page         Results per page.
     * @param string $current_url      Optional; the current URL where pagination links are displayed.
     * @param int    $pagination_limit Optional; pagination link limit.
     *
     * @return array An array of pagination links, indexed by page number.
     */
    protected static function paginate($current_page, $total_results, $per_page, $current_url = '', $pagination_limit = 10)
    {
        $current_page  = max(1, (int) $current_page);
        $total_results = max(0, (int) $total_results);
        $per_page      = max(1, (int) $per_page);
        $total_pages   = ceil($total_results / $per_page);

        if (!$current_url) {
            $current_url = is_ssl() ? 'https://' : 'http://';
            $current_url .= $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        }
        $p_var       = c_ws_plugin__s2member_pro_sc_member_list_in::p_var();
        $current_url = remove_query_arg($p_var, $current_url);

        $pagination       = array(); // Pagination links.
        $pagination_limit = max(1, (int) $pagination_limit);

        for ($_i = 1, $_show_dots = false; $_i <= $total_pages; ++$_i) {
            if ($_i === 1 || $_i === $total_pages || $_i >= $current_page - $pagination_limit || $_i <= $current_page + $pagination_limit) {
                if ($_i === $current_page) {
                    $pagination[$_i]['url']  = '';
                    $pagination[$_i]['text'] = (string) $_i;
                    $pagination[$_i]['link'] = (string) $_i;
                } else {
                    $pagination[$_i]['text'] = (string) $_i;
                    $pagination[$_i]['url']  = add_query_arg($p_var, $_i, $current_url);
                    $pagination[$_i]['link'] = '<a href="'.esc_attr(add_query_arg($p_var, $_i, $current_url)).'">'.(string) $_i.'</a>';
                }
                $_show_dots = true;
            } elseif ($_show_dots) {
                $pagination[$_i]['url']  = '';
                $pagination[$_i]['text'] = '...';
                $pagination[$_i]['link'] = '...';
                $_show_dots              = false;
            }
        } // unset($_i, $_show_dots);

        return $pagination;
    }
}
