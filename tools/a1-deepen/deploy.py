"""Generate the execute-php code that writes built lessons (out/<id>.html) to the live site.

Checks before writing anything: every new body matches its local SHA-256, and every live
lesson is unchanged since the backup (else that lesson is skipped and reported).
Usage: python3 deploy.py <id> [<id> ...] > deploy_x.php
"""
import hashlib, sys

BACKUP = '/home/u569339493/domains/englishfinders.com/ef-backups/a1-lessons-20260928-093847.json'
ids = [int(a) for a in sys.argv[1:]]
parts = ["wp_set_current_user( 1 );",
         "$bk = json_decode( (string) file_get_contents( '%s' ), true );" % BACKUP,
         "if ( ! is_array( $bk ) ) { return 'no backup'; }",
         "$new = array();", "$sha = array();"]
for i in ids:
    h = open('out/%d.html' % i).read()
    assert 'EFA1BODY' not in h
    parts.append("$new[%d] = <<<'EFA1BODY'\n%s\nEFA1BODY;" % (i, h))
    parts.append("$sha[%d] = '%s';" % (i, hashlib.sha256(h.encode()).hexdigest()))
parts.append("""$out = array( 'written' => array(), 'skipped' => array() );
foreach ( $new as $id => $body ) {
	if ( ! hash_equals( $sha[ $id ], hash( 'sha256', $body ) ) ) { return array( 'abort' => 'sha mismatch', 'id' => $id ); }
	if ( 'lesson' !== get_post_type( $id ) || ! isset( $bk[ $id ] ) ) { return array( 'abort' => 'not an A1 lesson', 'id' => $id ); }
}
foreach ( $new as $id => $body ) {
	if ( get_post_field( 'post_content', $id, 'raw' ) !== $bk[ $id ]['content'] ) { $out['skipped'][] = $id; continue; }
	$r = wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $body ) ), true );
	clean_post_cache( $id );
	$ok = ! is_wp_error( $r ) && hash_equals( $sha[ $id ], hash( 'sha256', get_post_field( 'post_content', $id, 'raw' ) ) );
	$out['written'][ $id ] = $ok ? mb_strlen( wp_strip_all_tags( $body ) ) : 'FAILED';
	do_action( 'litespeed_purge_post', $id );
}
return $out;""")
print('\n'.join(parts))
