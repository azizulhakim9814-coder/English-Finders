$efm_name = array( 'A1' => 'Beginner', 'A2' => 'Elementary', 'B1' => 'Intermediate', 'B2' => 'Upper-Intermediate', 'C1' => 'Advanced', 'C2' => 'Proficiency' );
$efm_first = function ( $t, $title ) {
	$t = trim( html_entity_decode( $t, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	$after = trim( ( false !== strpos( $title, ':' ) ) ? substr( $title, strpos( $title, ':' ) + 1 ) : $title );
	foreach ( array( $title, $after, 'Can-Do Task: ' . $after ) as $pre ) {
		if ( '' !== $pre && 0 === strpos( mb_strtolower( $t ), mb_strtolower( $pre ) ) ) {
			$t = trim( mb_substr( $t, mb_strlen( $pre ) ) );
		}
	}
	$t = preg_replace( '/^(Can-Do Task:\s*)/u', '', $t );
	$t = preg_replace( '/^(Hook|Warm-up|Warm up)\s*:\s*/u', '', $t );
	$t = preg_replace( '/^.{0,90}?\bHook:?\s+/u', '', $t, 1 );
	$t = preg_split( '/(?<=[a-z0-9\)\.\?!…"”])(Practice|Notice|Connect|Answer Key|Examples?|Can-Do|Model answer)(?=[A-Z0-9“"\'])/u', $t )[0];
	$t = preg_replace( '/^(Task|Rule|Structure|Form|Note):\s*/u', '', trim( $t ) );
	$out = '';
	foreach ( preg_split( '/(?<=[.!?])\s+(?=[A-Z“"])/u', $t ) as $p ) {
		if ( '' === trim( $p ) ) {
			continue;
		}
		$out = trim( $out . ' ' . trim( $p ) );
		if ( mb_strlen( $out ) >= 80 ) {
			break;
		}
	}
	return mb_strtoupper( mb_substr( $out, 0, 1 ) ) . mb_substr( $out, 1 );
};
$efm_fit = function ( $s, $limit = 158 ) {
	$s = trim( preg_replace( '/\s+/u', ' ', $s ) );
	if ( mb_strlen( $s ) <= $limit ) {
		return $s;
	}
	$cut = mb_substr( $s, 0, $limit - 1 );
	$sp  = mb_strrpos( $cut, ' ' );
	if ( false !== $sp ) {
		$cut = mb_substr( $cut, 0, $sp );
	}
	return preg_replace( '/[,;:—–\- ]+$/u', '', $cut ) . '…';
};
$efm_write = isset( $efm_write ) ? $efm_write : false;
$out = array();
$bk  = json_decode( (string) file_get_contents( '/home/u569339493/domains/englishfinders.com/ef-backups/a1-lessons-20260928-093847.json' ), true );
foreach ( array_keys( $bk ) as $id ) {
	$title = get_the_title( $id );
	$title = html_entity_decode( $title, ENT_QUOTES, 'UTF-8' );
	if ( preg_match( '/Worksheet/u', $title ) ) {
		continue;
	}
	$html = (string) get_post_field( 'post_content', $id, 'raw' );
	$seg  = '';
	foreach ( array( 'Hook</h2>', 'Your Task</h2>' ) as $mark ) {
		$p = strpos( $html, $mark );
		if ( false !== $p ) {
			$e   = strpos( $html, '</p>', $p );
			$seg = substr( $html, $p + strlen( $mark ), $e - $p - strlen( $mark ) );
			break;
		}
	}
	if ( '' === $seg ) {
		$e   = strpos( $html, '</p>' );
		$h   = strpos( $html, '</h2>' );
		$seg = substr( $html, $h + 5, $e - $h - 5 );
	}
	$txt = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $seg ) ) );
	$fs  = $efm_first( $txt, $title );
	if ( 0 === strpos( $title, 'Can-Do Task' ) ) {
		$task = trim( substr( $title, strpos( $title, ':' ) + 1 ) );
		$d    = "A1 Can-Do task: $task. $fs";
	} elseif ( preg_match( '/Welcome|Final|Before You Begin|Reading & Listening/u', $title ) ) {
		$d = "$title | Free A1 Beginner English course. $fs";
	} else {
		$d = "$title (A1 Beginner English): $fs";
	}
	$d          = $efm_fit( $d );
	$out[ $id ] = $d;
	if ( $efm_write ) {
		update_post_meta( $id, 'rank_math_description', $d );
	}
}
$lens = array_map( 'mb_strlen', $out );
return array( 'n' => count( $out ), 'min' => min( $lens ), 'max' => max( $lens ), 'md5' => md5( implode( "\n", $out ) ), 'write' => $efm_write, 'sample' => array_slice( $out, 0, 84, true ) );
