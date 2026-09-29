<?php
/**
 * Breadcrumb trail and structured data.
 *
 * Shared by every tool. Extracted when the second tool needed it — a copy in
 * each would drift, and the structured data especially has to stay consistent.
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

namespace EnglishFindersStudy\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Breadcrumb {
	/**
	 * Build the trail for the page a tool is embedded in.
	 *
	 * The intended shape is Home / Tools / <category> / <tool>, but no category
	 * archive exists yet, and a breadcrumb pointing at a page that does not
	 * exist is worse than a shorter one. The category step is added by
	 * filtering `efs_breadcrumb_trail` once those pages are built.
	 *
	 * @param string $fallback Label used when the shortcode is not on a page.
	 * @return list<array{name:string,url:string}>
	 */
	public static function trail( string $fallback ): array {
		$crumbs = array(
			array(
				'name' => __( 'Home', 'english-finders-study' ),
				'url'  => home_url( '/' ),
			),
		);

		$tools_url = (string) apply_filters( 'efs_tools_url', home_url( '/tools/' ) );

		if ( '' !== $tools_url ) {
			$crumbs[] = array(
				'name' => __( 'Tools', 'english-finders-study' ),
				'url'  => $tools_url,
			);
		}

		$page_id = get_queried_object_id();

		if ( $page_id > 0 && is_page() ) {
			foreach ( array_reverse( (array) get_post_ancestors( $page_id ) ) as $ancestor_id ) {
				$crumbs[] = array(
					'name' => (string) get_the_title( $ancestor_id ),
					'url'  => (string) get_permalink( $ancestor_id ),
				);
			}

			$crumbs[] = array(
				'name' => (string) get_the_title( $page_id ),
				'url'  => (string) get_permalink( $page_id ),
			);
		} else {
			$crumbs[] = array(
				'name' => $fallback,
				'url'  => '',
			);
		}

		/**
		 * Filter the breadcrumb trail.
		 *
		 * @param list<array{name:string,url:string}> $crumbs Trail.
		 */
		$crumbs = (array) apply_filters( 'efs_breadcrumb_trail', $crumbs );

		return array_values(
			array_filter(
				$crumbs,
				static fn ( $crumb ): bool => is_array( $crumb ) && '' !== trim( (string) ( $crumb['name'] ?? '' ) )
			)
		);
	}

	/**
	 * Visible trail.
	 *
	 * @param list<array{name:string,url:string}> $crumbs Trail.
	 */
	public static function markup( array $crumbs ): string {
		if ( empty( $crumbs ) ) {
			return '';
		}

		$last = count( $crumbs ) - 1;
		$out  = '<nav class="efs-quiz__breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'english-finders-study' ) . '"><ol>';

		foreach ( array_values( $crumbs ) as $index => $crumb ) {
			$out .= '<li>';

			// The final crumb is the current page, so it is not a link.
			if ( '' !== $crumb['url'] && $index < $last ) {
				$out .= '<a href="' . esc_url( $crumb['url'] ) . '">' . esc_html( $crumb['name'] ) . '</a>';
			} else {
				$out .= '<span aria-current="page">' . esc_html( $crumb['name'] ) . '</span>';
			}

			$out .= '</li>';
		}

		return $out . '</ol></nav>';
	}

	/**
	 * BreadcrumbList structured data.
	 *
	 * If an SEO plugin already emits a BreadcrumbList for the page, two on one
	 * URL is worse than none — hence the schema="0" attribute on each tool.
	 *
	 * @param list<array{name:string,url:string}> $crumbs Trail.
	 */
	public static function schema( array $crumbs ): string {
		if ( empty( $crumbs ) ) {
			return '';
		}

		$items = array();

		foreach ( array_values( $crumbs ) as $index => $crumb ) {
			$item = array(
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'name'     => $crumb['name'],
			);

			if ( '' !== $crumb['url'] ) {
				$item['item'] = $crumb['url'];
			}

			$items[] = $item;
		}

		return '<script type="application/ld+json">' . wp_json_encode(
			array(
				'@context'        => 'https://schema.org',
				'@type'           => 'BreadcrumbList',
				'itemListElement' => $items,
			)
		) . '</script>';
	}
}
