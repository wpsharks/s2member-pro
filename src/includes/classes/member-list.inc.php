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
     * Keeps Member List Users-table searches on the requested WordPress user columns.
     *
     * @param array         $search_columns Search columns WordPress would otherwise use.
     * @param string        $search         Search term.
     * @param WP_User_Query $query          User query, when WordPress supplies it.
     *
     * @return array Search columns.
     */
    public static function _search_columns_filter($search_columns = array(), $search = '', $query = null)
    {
        if (is_object($query) && !empty(self::$_query_filter_contexts[spl_object_hash($query)]['user_search_cols'])) {
            return self::$_query_filter_contexts[spl_object_hash($query)]['user_search_cols'];
        }
        return self::$_search_columns_for_filter ? self::$_search_columns_for_filter : $search_columns;
    }
    protected static $_search_columns_for_filter = array();

    /**
     * Associates the next Member List `WP_User_Query` with its SQL context.
     *
     * @param WP_User_Query $query User query being prepared.
     */
    public static function _query_prepare_filter($query)
    {
        if (!is_object($query) || !method_exists($query, 'get')) {
            return;
        }
        $token = (string) $query->get('s2member_pro_member_list_query_token');
        if (!$token || empty(self::$_query_filter_pending[$token])) {
            return;
        }
        $context = self::$_query_filter_pending[$token];
        //260915.2136 Third-party `pre_get_users` callbacks historically saw the Member List's normal WP search before s2Member's own query ran. Capture any search adjustment they made for the Users-table branch, then suppress WordPress's built-in AND search so `_query_filter()` can combine it with usermeta/Custom Field matches as one OR expression.
        $context['user_search'] = (string) $query->get('search');
        $query->set('search', '');
        if (isset($query->query_vars['s2member_pro_member_list_query_token'])) {
            unset($query->query_vars['s2member_pro_member_list_query_token']); // Temporary routing marker must not affect query SQL/cache identity.
        }
        self::$_query_filter_contexts[spl_object_hash($query)] = $context;
        unset(self::$_query_filter_pending[$token]);
    }
    protected static $_query_filter_pending = array();

    /**
     * Member List `WP_User_Query` SQL helper.
     *
     * @param WP_User_Query $query User query in progress.
     */
    public static function _query_filter($query)
    {
        global $wpdb;

        if (!is_object($query) || empty(self::$_query_filter_contexts[spl_object_hash($query)])) {
            return;
        }
        $context = self::$_query_filter_contexts[spl_object_hash($query)];

        if ($context['search']) {
            $search_clauses = array();
            $query->set('search', isset($context['user_search']) ? $context['user_search'] : $context['search']); // Restore the public query var after suppressing only WordPress's built-in AND search during preparation.

            if ($context['user_search_cols']) {
                $search = isset($context['user_search']) ? $context['user_search'] : $context['search'];
                if ($search === '') {
                    $user_search_sql = '1=1'; // A third-party `pre_get_users` callback that clears search made the legacy Users-table stage unfiltered.
                } else {
                    $leading_wild  = (ltrim($search, '*') !== $search);
                    $trailing_wild = (rtrim($search, '*') !== $search);
                    if ($leading_wild && $trailing_wild) {
                        $wild = 'both';
                    } elseif ($leading_wild) {
                        $wild = 'leading';
                    } elseif ($trailing_wild) {
                        $wild = 'trailing';
                    } else {
                        $wild = false;
                    }
                    if ($wild) {
                        $search = trim($search, '*');
                    }
                    //260915.2136 Let WordPress and existing `user_search_columns` filters build the normal Users-table part exactly as before; only the surrounding Member List query architecture changes.
                    $search_columns = apply_filters('user_search_columns', $context['user_search_cols'], $search, $query);
                    $user_search_sql = $query->get_search_sql($search, $search_columns, $wild);
                    $user_search_sql = $user_search_sql ? preg_replace('/^\s*AND\s+/i', '', $user_search_sql, 1) : '';
                }
                if ($user_search_sql) {
                    $search_clauses[] = $user_search_sql;
                }
            }
            if ($context['user_meta_search_cols']) {
                $meta_searches = array();
                foreach ($context['user_meta_search_cols'] as $_search_col) {
                    $meta_searches[] = $wpdb->prepare('(s2ml_sm.`meta_key` = %s AND s2ml_sm.`meta_value` REGEXP %s)', $_search_col, $context['search_regex']);
                }
                unset($_search_col);

                if ($meta_searches) {
                    //260915.2136 The legacy usermeta-search branch required first/last/nickname to exist, but username/email and Custom Field matches did not. Keep that branch-specific behavior while replacing its row-multiplying joins with yes/no EXISTS probes.
                    $meta_search_sql = '('.self::_profile_meta_exists_sql().' AND EXISTS (SELECT 1 FROM `'.$wpdb->usermeta.'` s2ml_sm WHERE s2ml_sm.`user_id` = `'.$wpdb->users.'`.`ID` AND ('.implode(' OR ', $meta_searches).')))';
                    $search_clauses[] = $meta_search_sql;
                }
            }
            if ($context['search_s2_custom_fields']) {
                $custom_fields_regex = self::_custom_fields_search_regex($context['search'], $context['s2_custom_field_search_cols']);
                $search_clauses[] = $wpdb->prepare('EXISTS (SELECT 1 FROM `'.$wpdb->usermeta.'` s2ml_scf WHERE s2ml_scf.`user_id` = `'.$wpdb->users.'`.`ID` AND s2ml_scf.`meta_key` = %s AND s2ml_scf.`meta_value` REGEXP %s)', $context['blog_prefix'].'s2member_custom_fields', $custom_fields_regex);
            }
            //260915.2136 Search directly in the final paginated query. The old implementation first fetched every matching ID into PHP, merged/deduplicated the full set, then sent those IDs back to MySQL in a second query before pagination.
            $query->query_where .= $search_clauses ? ' AND ('.implode(' OR ', $search_clauses).')' : ' AND 1=0';
        } else {
            //260915.2136 Ordinary Member Lists historically required first_name, last_name, and nickname through three WP_Meta_Query joins. EXISTS preserves the qualification rule without multiplying the main Users result before sorting/pagination.
            $query->query_where .= ' AND '.self::_profile_meta_exists_sql();
        }
    }
    protected static $_query_filter_contexts = array();

    /**
     * SQL requiring the three standard Member List profile values.
     *
     * @return string SQL expression.
     */
    protected static function _profile_meta_exists_sql()
    {
        global $wpdb;

        return "EXISTS (SELECT 1 FROM `{$wpdb->usermeta}` s2ml_fn WHERE s2ml_fn.`user_id` = `{$wpdb->users}`.`ID` AND s2ml_fn.`meta_key` = 'first_name' AND s2ml_fn.`meta_value` != '___')"
            ." AND EXISTS (SELECT 1 FROM `{$wpdb->usermeta}` s2ml_ln WHERE s2ml_ln.`user_id` = `{$wpdb->users}`.`ID` AND s2ml_ln.`meta_key` = 'last_name' AND s2ml_ln.`meta_value` != '___')"
            ." AND EXISTS (SELECT 1 FROM `{$wpdb->usermeta}` s2ml_nn WHERE s2ml_nn.`user_id` = `{$wpdb->users}`.`ID` AND s2ml_nn.`meta_key` = 'nickname' AND s2ml_nn.`meta_value` != '___')";
    }

    /**
     * Builds the legacy-compatible serialized Custom Fields search regex.
     *
     * @param string $search      Normalized search string.
     * @param array  $search_cols Custom Field columns to search; empty means all.
     *
     * @return string Regex.
     */
    protected static function _custom_fields_search_regex($search, $search_cols)
    {
        $custom_fields_regex_frag = '';

        foreach ((array) $search_cols as $_search_col) {
            if (preg_match('/^s2member_custom_field_(?P<field_id>\w+)$/', $_search_col, $_m)) {
                $custom_fields_regex_frag .= preg_quote(trim($_m['field_id'])).'|';
            }
        }
        unset($_search_col, $_m);
        $custom_fields_regex_frag = rtrim($custom_fields_regex_frag, '|');

        if (!$custom_fields_regex_frag) {
            $custom_fields_regex_frag = '.*';
        }
        $search_regex_frag = str_replace('\\*', '[^"]*', preg_quote(str_replace(array('"', '{', '}'), '', $search)));

        return '(^|\{|;)s\:[0-9]+\:"('.$custom_fields_regex_frag.')"(;s\:[0-9]+\:"'.$search_regex_frag.'"|;a\:[0-9]+\:\{i\:[0-9]+;[^}]*"'.$search_regex_frag.'")';
    }

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

        $list_max = apply_filters('ws_plugin__s2member_pro_member_list_max', 250);

        $args['who']         = '';
        $args['count_total'] = true;
        $args['fields']      = 'all_with_meta';
        $args['number']      = min($args['number'], $list_max);
        $args['number']      = max(1, $args['number']);
        $args['offset']      = ($page - 1) * $args['number'];

        /* ---------------------------------------------------------- */

        //260915.2211 Keep WordPress in charge of the final Member List query (custom meta_query, Roles/Levels/CCAPs, include/exclude, Multisite membership, ordering, totals, pagination, caching, and user hydration), but add the expensive profile/search conditions as EXISTS clauses instead of building full matching-ID lists in PHP.
        $token = uniqid('s2ml_', true);
        self::$_query_filter_pending[$token] = array(
            'search'                      => $args['search'],
            'search_regex'                => $search_regex,
            'user_search_cols'            => array_values($user_search_cols),
            'user_meta_search_cols'       => array_values($user_meta_search_cols),
            's2_custom_field_search_cols' => array_values($s2_custom_field_search_cols),
            'search_s2_custom_fields'     => $search_s2_custom_fields,
            'blog_prefix'                 => $blog_prefix,
        );
        $args['s2member_pro_member_list_query_token'] = $token; // Removed by `_query_prepare_filter()` before WordPress builds SQL/cache keys.

        if ($args['search']) {
            $search_orderby = $args['orderby'];
            //260915.2211 Keep `include`/`exclude` on the final WP_User_Query so they consistently constrain the Member List regardless of which search branch matched. The old separate Custom Fields lookup could bypass `include`; that was an implementation leak, not useful list semantics.

            // If ordering by one of the meta values.
            if (in_array($search_orderby, array('first_name', 'last_name', 'nickname'))) {
                $args['orderby']  = 'meta_value';
                $args['meta_key'] = $search_orderby;
            }
            unset($search_orderby);
        }
        self::$_search_columns_for_filter = array_values($user_search_cols);
        if ($user_search_cols && $args['search']) {
            add_filter('user_search_columns', 'c_ws_plugin__s2member_pro_member_list::_search_columns_filter', 10, 3);
        }
        //260915.2136 Capture the target query at the end of `pre_get_users`, after integrations have had their normal chance to adjust it; then inject s2Member's optimized conditions before ordinary `pre_user_query` callbacks inspect/modify the final SQL.
        add_action('pre_get_users', 'c_ws_plugin__s2member_pro_member_list::_query_prepare_filter', PHP_INT_MAX);
        add_action('pre_user_query', 'c_ws_plugin__s2member_pro_member_list::_query_filter', -PHP_INT_MAX);

        $query = new WP_User_Query($args);

        remove_action('pre_get_users', 'c_ws_plugin__s2member_pro_member_list::_query_prepare_filter', PHP_INT_MAX);
        remove_action('pre_user_query', 'c_ws_plugin__s2member_pro_member_list::_query_filter', -PHP_INT_MAX);
        if ($user_search_cols && $args['search']) {
            remove_filter('user_search_columns', 'c_ws_plugin__s2member_pro_member_list::_search_columns_filter');
        }
        unset(self::$_query_filter_pending[$token], self::$_query_filter_contexts[spl_object_hash($query)]);
        self::$_search_columns_for_filter = array();

        return array(
            'query'      => $query,
            'pagination' => self::paginate(
                $page, // Current page.
                (int) $query->get_total(),
                $args['number']
            ),
        );
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
