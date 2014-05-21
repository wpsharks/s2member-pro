<?php
/**
 * Members List
 *
 * Copyright: © 2009-2011
 * {@link http://www.websharks-inc.com/ WebSharks, Inc.}
 * (coded in the USA)
 *
 * Released under the terms of the GNU General Public License.
 * You should have received a copy of the GNU General Public License,
 * along with this software. In the main directory, see: /licensing/
 * If not, see: {@link http://www.gnu.org/licenses/}.
 *
 * @package s2Member\Member_List
 * @since 140502
 */
if(realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME']))
	exit ('Do not access this file directly.');

if(!class_exists('c_ws_plugin__s2member_pro_member_list'))
{
	/**
	 * Members List
	 *
	 * @package s2Member\Member_List
	 * @since 140502
	 */
	class c_ws_plugin__s2member_pro_member_list
	{
		/**
		 * Members List; WP User Query wrapper.
		 *
		 * @param array $args Optional array of arguments to {@link \WP_User_Query}
		 *
		 * @return array Containing the following keys: `query` and `pagination`.
		 */
		public static function query($args = array())
		{
			if(!is_array($args))
				$args = array();

			$p_var = c_ws_plugin__s2member_pro_sc_member_list_in::p_var();
			if(empty($_REQUEST[$p_var]) || ($page = (integer)$_REQUEST[$p_var]) < 1)
				$page = 1; // Default page number.

			$default_args = array(
				'blog_id' => $GLOBALS['blog_id'],

				'role'    => '', 'meta_key' => '', 'meta_value' => '', 'meta_compare' => '', 'meta_query' => array(),
				'search'  => '', 'search_columns' => array('ID', 'user_login', 'user_email', 'user_url', 'user_nicename'),
				'include' => array(), 'exclude' => array(),

				'order'   => 'DESC', 'orderby' => 'registered', 'number' => 25
			);
			if(!empty($args['args']))
			{
				$args = wp_parse_args($args['args']);
				$args = array_merge($default_args, $args);
			}
			else // Merge with individual args.
			{
				unset($args['args']); // Do not use.
				$args = array_merge($default_args, $args);
			}
			foreach($args as $_key => &$_value) // Typecast argument values.
			{
				if(in_array($_key, array('count_total'), TRUE))
					$_value = filter_var($_value, FILTER_VALIDATE_BOOLEAN);

				else if(in_array($_key, array('blog_id', 'offset', 'number'), TRUE))
					$_value = (integer)$_value; // Integer value.

				else if(in_array($_key, array('meta_query', 'search_columns', 'include', 'exclude'), TRUE))
					$_value = $_value ? (array)$_value : array();

				else if(in_array($_key, array('fields'), TRUE))
					$_value = is_array($_value) ? $_value : (string)$_value;

				else if(in_array($_key, array('role', 'search', 'who',
				                              'meta_key', 'meta_value', 'meta_compare',
				                              'order', 'orderby'), TRUE)
				) $_value = (string)$_value;
			}
			unset($_key, $_value); // Housekeeping.

			$args['who']         = '';
			$args['count_total'] = TRUE;
			$args['fields']      = 'all_with_meta';
			$args['number']      = min($args['number'], apply_filters('ws_plugin__s2member_pro_member_list_max', 250, get_defined_vars()));
			if($args['number'] < 1) $args['number'] = 1; // Make sure this is always >= 1.
			$args['offset'] = ($page - 1) * $args['number']; // Calculate dynamically.

			$query = new WP_User_Query($args); // See <https://codex.wordpress.org/Class_Reference/WP_User_Query>
			return array('query' => $query, 'pagination' => self::paginate($page, (integer)$query->get_total(), $args['number']));
		}

		/**
		 * Pagination handler.
		 *
		 * @param integer $current_page Current page number.
		 * @param integer $total_results Total results.
		 * @param integer $per_page Results per page.
		 * @param string  $current_url Optional; the current URL where pagination links are displayed.
		 * @param integer $pagination_limit Optional; pagination link limit.
		 *
		 * @return array An array of pagination links, indexed by page number.
		 */
		protected static function paginate($current_page, $total_results, $per_page, $current_url = '', $pagination_limit = 10)
		{
			$current_page  = max(1, (integer)$current_page);
			$total_results = max(0, (integer)$total_results);
			$per_page      = max(1, (integer)$per_page);
			$total_pages   = ceil($total_results / $per_page);

			if(!$current_url) // We can auto-detect this.
			{
				$current_url = is_ssl() ? 'https://' : 'http://';
				$current_url .= $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			}
			$p_var       = c_ws_plugin__s2member_pro_sc_member_list_in::p_var();
			$current_url = remove_query_arg($p_var, $current_url);

			$pagination       = array(); // Array of pagination links.
			$pagination_limit = max(1, (integer)$pagination_limit);

			for($_i = 1, $_show_dots = FALSE; $_i <= $total_pages; $_i++)
			{
				if($_i === 1 || $_i === $total_pages
				   || $_i >= $current_page - $pagination_limit || $_i <= $current_page + $pagination_limit
				) // First or last; or within the current pagination limit.
				{
					if($_i === $current_page)
					{
						$pagination[$_i]['url']  = '';
						$pagination[$_i]['text'] = (string)$_i;
						$pagination[$_i]['link'] = (string)$_i;
					}
					else // It's another page available.
					{
						$pagination[$_i]['text'] = (string)$_i;
						$pagination[$_i]['url']  = add_query_arg($p_var, $_i, $current_url);
						$pagination[$_i]['link'] = '<a href="'.esc_attr(add_query_arg($p_var, $_i, $current_url)).'">'.(string)$_i.'</a>';
					}
					$_show_dots = TRUE;
				}
				else if($_show_dots)
				{
					$pagination[$_i]['url']  = '';
					$pagination[$_i]['text'] = '...';
					$pagination[$_i]['link'] = '...';
					$_show_dots              = FALSE;
				}
			}
			unset($_i, $_show_dots);

			return $pagination;
		}
	}
}