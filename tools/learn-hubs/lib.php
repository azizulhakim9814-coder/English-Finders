if ( ! function_exists( 'efl_card' ) ) {
	function efl_card( $g0, $g1, $icon, $pills, $title, $desc, $url, $verb = 'Open' ) {
		$ph = '';
		foreach ( $pills as $p ) {
			$ph .= '<span style="background:#f3f4f6;color:#374151;font-size:11.5px;font-weight:700;padding:4px 10px;border-radius:999px;border:1px solid #e5e7eb;">' . esc_html( $p ) . '</span>';
		}
		$t = esc_html( $title );
		$u = esc_url( $url );
		return '<div class="ef-game-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden;box-shadow:0 1px 2px rgba(16,24,40,.04);display:flex;flex-direction:column;height:350px;font-family:Open Sans,sans-serif;">'
			. '<div style="height:96px;flex:0 0 96px;background:linear-gradient(135deg,' . $g0 . ',' . $g1 . ');display:flex;align-items:center;justify-content:center;">'
			. '<div style="width:56px;height:56px;border-radius:14px;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 10px rgba(0,0,0,.15);">'
			. '<i class="fas ' . $icon . '" style="font-size:22px;color:#1f2937;"></i>'
			. '</div></div>'
			. '<div style="padding:20px 20px 22px;display:flex;flex-direction:column;gap:10px;flex:1 1 auto;min-height:0;">'
			. '<div style="display:flex;gap:8px;flex-wrap:wrap;flex:0 0 auto;">' . $ph . '</div>'
			. '<div style="font-size:18px;font-weight:800;color:#1d4ed8;line-height:1.3;flex:0 0 auto;"><a href="' . $u . '" style="color:inherit;text-decoration:none;">' . $t . '</a></div>'
			. '<div style="font-size:13.5px;color:#4b5563;line-height:1.55;flex:1 1 auto;overflow:hidden;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;">' . esc_html( $desc ) . '</div>'
			. '<a href="' . $u . '" style="font-size:14px;font-weight:700;color:#1d4ed8;text-decoration:none;margin-top:2px;display:flex;align-items:center;gap:5px;flex:0 0 auto;width:100%;">'
			. '<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0;flex:1 1 auto;">' . esc_html( $verb . ' ' . $title ) . '</span>'
			. '<span aria-hidden="true" style="flex:0 0 auto;">&#8594;</span>'
			. '</a></div></div>';
	}

	function efl_grid( array $cards, $per_page = 12 ) {
		$items = implode( '', array_map( fn( $h ) => '<div class="ef-hub-item">' . $h . '</div>', $cards ) );
		$st    = '<' . 'style>';
		$ste   = '</' . 'style>';
		$sc    = '<' . 'script>';
		$sce   = '</' . 'script>';
		$css   = '.ef-hub-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px}'
			. '@media (max-width:1024px){.ef-hub-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}'
			. '@media (max-width:640px){.ef-hub-grid{grid-template-columns:1fr}}'
			. '.ef-hub-pagination{align-items:center;display:flex;flex-wrap:wrap;gap:.7rem;justify-content:center;margin-top:28px}'
			. '.ef-hub-pagination button{background:#fff;border:1px solid #d9e3ef;border-radius:.75rem;color:#172033;cursor:pointer;font:700 .9rem/1.2 Open Sans,sans-serif;min-block-size:44px;padding:.6rem .85rem;white-space:nowrap}'
			. '.ef-hub-pagination button:hover:not(:disabled),.ef-hub-pagination button:focus-visible{border-color:#0b6cf0;box-shadow:0 0 0 3px rgba(11,108,240,.13);outline:0}'
			. '.ef-hub-pagination button:disabled{opacity:.45;cursor:not-allowed}'
			. '.ef-hub-pagination span{color:#65758a;font-weight:700;font-size:.9rem}';
		$js    = '(function(){document.querySelectorAll("[data-ef-hub-grid]").forEach(function(root){if(root.dataset.efHubInit)return;root.dataset.efHubInit="1";'
			. 'var perPage=parseInt(root.dataset.perPage||"12",10),itemsContainer=root.querySelector("[data-ef-hub-items]"),items=Array.prototype.slice.call(itemsContainer.children),pagination=root.querySelector("[data-ef-hub-pagination]"),totalPages=Math.max(1,Math.ceil(items.length/perPage)),page=1;'
			. 'function scrollToTop(){root.scrollIntoView({behavior:window.matchMedia("(prefers-reduced-motion: reduce)").matches?"auto":"smooth",block:"start"});}'
			. 'function render(){var start=(page-1)*perPage,end=start+perPage;items.forEach(function(item,i){item.style.display=(i>=start&&i<end)?"":"none";});pagination.replaceChildren();'
			. 'if(totalPages>1){pagination.hidden=false;var prev=document.createElement("button");prev.type="button";prev.textContent="Previous";prev.disabled=page<=1;prev.addEventListener("click",function(){page-=1;render();scrollToTop();});'
			. 'var label=document.createElement("span");label.textContent="Page "+page+" / "+totalPages;var next=document.createElement("button");next.type="button";next.textContent="Next";next.disabled=page>=totalPages;next.addEventListener("click",function(){page+=1;render();scrollToTop();});pagination.append(prev,label,next);}else{pagination.hidden=true;}}'
			. 'render();});})();';
		return $st . $css . $ste
			. '<div class="ef-hub-wrap" data-ef-hub-grid data-per-page="' . (int) $per_page . '">'
			. '<div class="ef-hub-grid" data-ef-hub-items>' . $items . '</div>'
			. '<nav class="ef-hub-pagination" data-ef-hub-pagination aria-label="Pages" hidden></nav>'
			. '</div>'
			. $sc . $js . $sce;
	}

	function efl_id() {
		return substr( md5( uniqid( '', true ) . wp_rand() ), 0, 7 );
	}

	function efl_hero( $title, $desc ) {
		return array(
			'id'       => efl_id(),
			'elType'   => 'section',
			'settings' => array(
				'background_background'     => 'gradient',
				'background_color'          => '#035DA2',
				'background_color_b'        => '#003bb1',
				'background_gradient_angle' => array( 'unit' => 'deg', 'size' => 120, 'sizes' => array() ),
				'background_gradient_type'  => 'radial',
				'padding'                   => array( 'unit' => 'px', 'top' => '129', 'right' => '129', 'bottom' => '129', 'left' => '129', 'isLinked' => true ),
				'padding_tablet'            => array( 'unit' => 'px', 'top' => '150', 'right' => '0', 'bottom' => '80', 'left' => '0', 'isLinked' => false ),
				'padding_mobile'            => array( 'unit' => 'px', 'top' => '150', 'right' => '10', 'bottom' => '80', 'left' => '10', 'isLinked' => false ),
				'content_width'             => array( 'unit' => 'px', 'size' => 750, 'sizes' => array() ),
			),
			'elements' => array(
				array(
					'id'       => efl_id(),
					'elType'   => 'column',
					'settings' => array( '_column_size' => 100, '_inline_size' => 100 ),
					'elements' => array(
						array(
							'id'         => efl_id(),
							'elType'     => 'widget',
							'widgetType' => 'image-box',
							'settings'   => array(
								'image'              => array( 'url' => '', 'id' => '' ),
								'title_text'         => $title,
								'description_text'   => $desc,
								'title_size'         => 'h1',
								'text_align'         => 'center',
								'title_bottom_space' => array( 'unit' => 'px', 'size' => 10, 'sizes' => array() ),
								'title_color'        => '#ffffff',
								'description_color'  => '#ffffff',
								'_margin'            => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ),
								'_padding'           => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ),
							),
							'elements'   => array(),
						),
					),
				),
			),
		);
	}

	/** One boxed section holding the given widgets, top/bottom padding in px. */
	function efl_section( array $widgets, $top = 40, $bottom = 24 ) {
		return array(
			'id'       => efl_id(),
			'elType'   => 'section',
			'settings' => array(
				'padding'        => array( 'unit' => 'px', 'top' => (string) $top, 'right' => '0', 'bottom' => (string) $bottom, 'left' => '0', 'isLinked' => false ),
				'padding_mobile' => array( 'unit' => 'px', 'top' => (string) min( $top, 32 ), 'right' => '16', 'bottom' => '16', 'left' => '16', 'isLinked' => false ),
			),
			'elements' => array(
				array(
					'id'       => efl_id(),
					'elType'   => 'column',
					'settings' => array( '_column_size' => 100, '_inline_size' => 100 ),
					'elements' => $widgets,
				),
			),
		);
	}

	function efl_w_html( $html ) {
		return array( 'id' => efl_id(), 'elType' => 'widget', 'widgetType' => 'html', 'settings' => array( 'html' => $html ), 'elements' => array() );
	}

	function efl_w_heading( $text, $sub = '' ) {
		$w = array(
			array(
				'id'         => efl_id(),
				'elType'     => 'widget',
				'widgetType' => 'heading',
				'settings'   => array( 'title' => $text, 'header_size' => 'h2', 'align' => 'center', 'title_color' => '#172033' ),
				'elements'   => array(),
			),
		);
		if ( '' !== $sub ) {
			$w[] = array(
				'id'         => efl_id(),
				'elType'     => 'widget',
				'widgetType' => 'text-editor',
				'settings'   => array( 'editor' => '<p style="text-align:center;color:#4b5563;max-width:640px;margin:0 auto;">' . esc_html( $sub ) . '</p>', '_margin' => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '16', 'left' => '0', 'isLinked' => false ) ),
				'elements'   => array(),
			);
		}
		return $w;
	}

	function efl_w_shortcode( $sc ) {
		return array( 'id' => efl_id(), 'elType' => 'widget', 'widgetType' => 'shortcode', 'settings' => array( 'shortcode' => $sc, '_margin' => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '32', 'left' => '0', 'isLinked' => false ) ), 'elements' => array() );
	}

	/** Create or update a page and save its Elementor tree the supported way. */
	function efl_save_page( $slug, $title, $parent, array $sections, $rm_desc, $rm_kw ) {
		$existing = get_page_by_path( $parent ? get_post_field( 'post_name', $parent ) . '/' . $slug : $slug );
		$id       = $existing ? $existing->ID : wp_insert_post( array( 'post_title' => $title, 'post_name' => $slug, 'post_parent' => $parent, 'post_type' => 'page', 'post_status' => 'publish', 'post_content' => '' ) );
		if ( is_wp_error( $id ) || ! $id ) {
			return 'insert failed';
		}
		update_post_meta( $id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $id, '_elementor_template_type', 'wp-page' );
		update_post_meta( $id, '_elementor_version', '4.2.4' );
		update_post_meta( $id, '_wp_page_template', 'elementor_header_footer' );
		update_post_meta( $id, 'ast-title-bar-display', 'disabled' );
		update_post_meta( $id, '_elementor_page_settings', array() );
		update_post_meta( $id, 'rank_math_description', $rm_desc );
		update_post_meta( $id, 'rank_math_focus_keyword', $rm_kw );
		$doc = \Elementor\Plugin::$instance->documents->get( $id, false );
		$doc->save( array( 'elements' => $sections ) );
		wp_update_post( array( 'ID' => $id ) );
		clean_post_cache( $id );
		$css = new \Elementor\Core\Files\CSS\Post( $id );
		$css->delete();
		$css->update();
		do_action( 'litespeed_purge_post', $id );
		$saved = json_decode( (string) get_post_meta( $id, '_elementor_data', true ), true );
		return array( 'id' => $id, 'url' => wp_make_link_relative( get_permalink( $id ) ), 'sections_saved' => is_array( $saved ) ? count( $saved ) : 0, 'cards' => substr_count( (string) get_post_meta( $id, '_elementor_data', true ), 'ef-game-card' ) );
	}
}
