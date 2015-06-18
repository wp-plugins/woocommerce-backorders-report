<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Report_Stock' ) ) {
	require_once( ABSPATH . 'wp-content/plugins/woocommerce/includes/admin/reports/class-wc-report-stock.php' );
}

/**
 * WC_Report_Backorders
 *
 * @author      Filament Studios
 * @category    Admin
 * @version     1.0
 */
class WC_Report_Backorders extends WC_Report_Stock {

	/**
	 * No items found text
	 */
	public function no_items() {
		_e( 'No backordered products found.', 'wcbr' );
	}

	/**
	 * Get Products matching stock criteria
	 */
	public function get_items() {
		global $wpdb;

		$this->items     = array();

		// Get products using a query - this is too advanced for get_posts :(
		$nostock = absint( max( get_option( 'woocommerce_notify_no_stock_amount' ), 0 ) );

		$query_from = "FROM {$wpdb->posts} as posts
			INNER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id
			INNER JOIN {$wpdb->postmeta} AS postmeta2 ON posts.ID = postmeta2.post_id
			WHERE 1=1
			AND posts.post_type IN ( 'product', 'product_variation' )
			AND posts.post_status = 'publish'
			AND postmeta.meta_key = '_backorders' AND postmeta.meta_value != 'no'
			AND postmeta2.meta_key = '_stock' AND CAST(postmeta2.meta_value AS SIGNED) < '{$nostock}'
		";

		$this->items     = $wpdb->get_results( "SELECT posts.ID as id, posts.post_parent as parent {$query_from} GROUP BY posts.ID ORDER BY posts.post_title" );

	}

	/**
	 * Output the report
	 */
	public function output_report() {
		global $wcbr_total_backorder_items;

		$this->prepare_items();
		echo '<div id="poststuff" class="woocommerce-reports-wide">';
		$this->display();
		echo '</div>';
		echo '<p>';
			_e( 'Total Items On Backorder', 'wcbr' );
			echo ': ' . $wcbr_total_backorder_items;
		echo '</p>';
	}

	public function column_default( $item, $column_name ) {
		global $product, $wcbr_total_backorder_items;

		if ( ! $product || $product->id !== $item->id ) {
			$product = wc_get_product( $item->id );
		}

		if ( ! $wcbr_total_backorder_items ) {
			$wcbr_total_backorder_items = 0;
		}

		switch( $column_name ) {

			case 'product' :
				if ( $sku = $product->get_sku() ) {
					echo $sku . ' - ';
				}

				echo $product->get_title();

				// Get variation data
				if ( $product->is_type( 'variation' ) ) {
					$list_attributes = array();
					$attributes = $product->get_variation_attributes();

					foreach ( $attributes as $name => $attribute ) {
						$list_attributes[] = wc_attribute_label( str_replace( 'attribute_', '', $name ) ) . ': <strong>' . $attribute . '</strong>';
					}

					echo '<div class="description">' . implode( ', ', $list_attributes ) . '</div>';
				}
			break;

			case 'categories' :
				$item_categories = apply_filters( 'wcbr_product_categories', get_the_terms( $product->id, 'product_cat' ), $product );
				if ( ! empty( $item_categories ) ) {
					$term_names = wp_list_pluck( $item_categories, 'name' );
					echo implode( ', ', $term_names );
				} else {
					echo '-';
				}

			break;

			case 'parent' :
				if ( $item->parent ) {
					echo get_the_title( $item->parent );
				} else {
					echo '-';
				}
			break;

			case 'units_backordered' :
				$backorders = - ( $product->get_stock_quantity() );
				echo $backorders;
				$wcbr_total_backorder_items += $backorders;
			break;

			case 'wc_actions' :
				?><p>
					<?php
						$actions = array();
						$action_id = $product->is_type( 'variation' ) ? $item->parent : $item->id;

						$actions['edit'] = array(
							'url'       => admin_url( 'post.php?post=' . $action_id . '&action=edit' ),
							'name'      => __( 'Edit', 'wcbr' ),
							'action'    => "edit"
						);

						if ( $product->is_visible() ) {
							$actions['view'] = array(
								'url'       => get_permalink( $action_id ),
								'name'      => __( 'View', 'wcbr' ),
								'action'    => "view"
							);
						}

						$actions = apply_filters( 'woocommerce_admin_stock_report_product_actions', $actions, $product );

						foreach ( $actions as $action ) {
							printf( '<a class="button tips %s" href="%s" data-tip="%s ' . __( 'product', 'wcbr' ) . '">%s</a>', $action['action'], esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
						}
					?>
				</p><?php
			break;
		}
	}

	/**
	 * get_columns function.
	 */
	public function get_columns() {

		$columns = array(
			'product'           => __( 'Product', 'wcbr' ),
			'categories'        => __( 'Categories', 'wcbr' ),
			'units_backordered' => __( 'Units backordered', 'wcbr' ),
			'parent'            => __( 'Parent', 'wcbr' ),
			'wc_actions'        => __( 'Actions', 'wcbr' ),
		);

		return $columns;
	}

	/**
	 * prepare_items function.
	 */
	public function prepare_items() {
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$this->get_items();
	}
}
